<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class MediaService
{
    /**
     * @var array<string, string>
     */
    private const IMAGE_MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAssetById(int $assetId): ?array
    {
        if ($assetId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM media_assets
             WHERE id = :id
             LIMIT 1',
            ['id' => $assetId]
        )->fetch();

        return is_array($row) ? $this->hydrateAsset($row) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAssets(?string $collectionKey = null, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $params = [];
        $sql = 'SELECT *
                FROM media_assets';

        if ($collectionKey !== null && trim($collectionKey) !== '') {
            $sql .= ' WHERE collection_key = :collection_key';
            $params['collection_key'] = trim($collectionKey);
        }

        $sql .= ' ORDER BY created_at DESC, id DESC
                  LIMIT ' . $limit;

        $rows = $this->app->database()->fetchAll($sql, $params);

        return array_map(fn (array $row): array => $this->hydrateAsset($row), $rows);
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function uploadAsset(array $file, string $collectionKey, ?string $altText = null, ?int $uploadedByAdminId = null): array
    {
        $normalizedCollectionKey = $this->normalizeCollectionKey($collectionKey);

        if ($normalizedCollectionKey === '') {
            throw new \RuntimeException('Collection key is required.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = trim((string) ($file['name'] ?? ''));

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('A valid uploaded file is required.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpName);
        $extension = self::IMAGE_MIME_MAP[$mimeType] ?? null;

        if ($extension === null) {
            throw new \RuntimeException('Unsupported media type.');
        }

        $targetDirectory = $this->app->getBasePath('public/uploads/cms/' . $normalizedCollectionKey);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new \RuntimeException('CMS media upload directory is not writable.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $diskPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;
        $publicPath = '/uploads/cms/' . $normalizedCollectionKey . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $diskPath)) {
            throw new \RuntimeException('Unable to move uploaded media asset.');
        }

        $dimensions = @getimagesize($diskPath);
        $width = is_array($dimensions) && isset($dimensions[0]) ? (int) $dimensions[0] : null;
        $height = is_array($dimensions) && isset($dimensions[1]) ? (int) $dimensions[1] : null;

        $this->app->database()->execute(
            'INSERT INTO media_assets (
                collection_key,
                file_name,
                original_name,
                disk_path,
                public_path,
                mime_type,
                extension,
                file_size,
                alt_text,
                width,
                height,
                uploaded_by_admin_id
             ) VALUES (
                :collection_key,
                :file_name,
                :original_name,
                :disk_path,
                :public_path,
                :mime_type,
                :extension,
                :file_size,
                :alt_text,
                :width,
                :height,
                :uploaded_by_admin_id
             )',
            [
                'collection_key' => $normalizedCollectionKey,
                'file_name' => $fileName,
                'original_name' => $originalName !== '' ? $originalName : $fileName,
                'disk_path' => $diskPath,
                'public_path' => $publicPath,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'file_size' => is_file($diskPath) ? filesize($diskPath) : 0,
                'alt_text' => $this->normalizeAltText($altText),
                'width' => $width,
                'height' => $height,
                'uploaded_by_admin_id' => $uploadedByAdminId,
            ]
        );

        $assetId = (int) $this->app->database()->connection()->lastInsertId();
        $asset = $this->findAssetById($assetId);

        if ($asset === null) {
            throw new \RuntimeException('Uploaded media asset could not be loaded.');
        }

        return $asset;
    }

    public function updateAltText(int $assetId, ?string $altText): void
    {
        if ($assetId <= 0 || $this->findAssetById($assetId) === null) {
            throw new \RuntimeException('Media asset not found.');
        }

        $this->app->database()->execute(
            'UPDATE media_assets
             SET alt_text = :alt_text
             WHERE id = :id',
            [
                'id' => $assetId,
                'alt_text' => $this->normalizeAltText($altText),
            ]
        );
    }

    public function deleteAsset(int $assetId): void
    {
        $asset = $this->findAssetById($assetId);

        if ($asset === null) {
            throw new \RuntimeException('Media asset not found.');
        }

        if ($this->isAssetInUse($assetId)) {
            throw new \RuntimeException('This media asset is still assigned to website content and cannot be deleted.');
        }

        $this->app->database()->execute(
            'DELETE FROM media_assets
             WHERE id = :id',
            ['id' => $assetId]
        );

        $diskPath = (string) ($asset['disk_path'] ?? '');

        if ($diskPath !== '' && is_file($diskPath)) {
            @unlink($diskPath);
        }
    }

    /**
     * @param array<int, int> $assetIds
     * @return array<int, array<string, mixed>>
     */
    public function listAssetsByIds(array $assetIds): array
    {
        $assetIds = array_values(array_filter(array_map('intval', $assetIds), static fn (int $id): bool => $id > 0));

        if ($assetIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($assetIds), '?'));
        $rows = $this->app->database()->fetchAll(
            'SELECT *
             FROM media_assets
             WHERE id IN (' . $placeholders . ')',
            $assetIds
        );

        return array_map(fn (array $row): array => $this->hydrateAsset($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateAsset(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['file_size'] = (int) ($row['file_size'] ?? 0);
        $row['width'] = isset($row['width']) ? (int) $row['width'] : null;
        $row['height'] = isset($row['height']) ? (int) $row['height'] : null;
        $row['uploaded_by_admin_id'] = isset($row['uploaded_by_admin_id']) ? (int) $row['uploaded_by_admin_id'] : null;
        $row['public_path'] = $this->canonicalPublicPath(
            (string) ($row['public_path'] ?? ''),
            (string) ($row['collection_key'] ?? ''),
            (string) ($row['file_name'] ?? '')
        );

        return $row;
    }

    private function canonicalPublicPath(string $publicPath, string $collectionKey, string $fileName): string
    {
        $normalized = trim(str_replace('\\', '/', $publicPath));

        if ($normalized !== '' && preg_match('#^https?://#i', $normalized) === 1) {
            $parsedPath = (string) (parse_url($normalized, PHP_URL_PATH) ?? '');
            $normalized = $parsedPath !== '' ? $parsedPath : $normalized;
        }

        if ($normalized === '' || strpos($normalized, '/uploads/') !== 0) {
            $collectionKey = $this->normalizeCollectionKey($collectionKey);
            if ($collectionKey !== '' && $fileName !== '') {
                return '/uploads/cms/' . $collectionKey . '/' . ltrim($fileName, '/');
            }
        }

        if ($normalized !== '' && $normalized[0] !== '/') {
            $normalized = '/' . $normalized;
        }

        return $normalized;
    }

    private function normalizeCollectionKey(string $collectionKey): string
    {
        $collectionKey = strtolower(trim($collectionKey));

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $collectionKey) === 1 ? $collectionKey : '';
    }

    private function normalizeAltText(?string $altText): ?string
    {
        if ($altText === null) {
            return null;
        }

        $altText = trim($altText);

        return $altText !== '' ? $altText : null;
    }

    private function isAssetInUse(int $assetId): bool
    {
        $checks = [
            'SELECT COUNT(*) AS total FROM banners WHERE media_asset_id = :id',
            'SELECT COUNT(*) AS total FROM content_blocks WHERE media_asset_id = :id',
            'SELECT COUNT(*) AS total FROM content_block_items WHERE media_asset_id = :id',
        ];

        foreach ($checks as $sql) {
            $row = $this->app->database()->query($sql, ['id' => $assetId])->fetch();

            if ((int) ($row['total'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
