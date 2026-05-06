<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\AdminSelectionService;
use App\Services\MediaService;

final class MediaController extends BaseAdminController
{
    private MediaService $mediaService;
    private AdminSelectionService $adminSelectionService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->mediaService = new MediaService($app);
        $this->adminSelectionService = new AdminSelectionService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();

        $collectionKey = trim((string) ($_GET['collection_key'] ?? ''));

        return $this->renderAdmin('admin-media-library', [
            'pageTitle' => 'Media Library',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'collectionKey' => $collectionKey,
            'collectionOptions' => $this->adminSelectionService->mediaCollectionOptions(),
            'assets' => $this->mediaService->listAssets($collectionKey !== '' ? $collectionKey : null),
        ]);
    }

    public function upload(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/media');
        }

        $collectionKey = trim((string) ($_POST['collection_key'] ?? ''));
        $altText = trim((string) ($_POST['alt_text'] ?? ''));
        $uploadedByAdminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;

        try {
            $this->mediaService->uploadAsset(
                is_array($_FILES['asset'] ?? null) ? $_FILES['asset'] : [],
                $collectionKey,
                $altText !== '' ? $altText : null,
                $uploadedByAdminId
            );
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $redirect = '/admin/media' . ($collectionKey !== '' ? '?collection_key=' . urlencode($collectionKey) : '');
            $this->redirect($redirect);
        }

        $this->flash('success', 'Media asset uploaded.');
        $redirect = '/admin/media' . ($collectionKey !== '' ? '?collection_key=' . urlencode($collectionKey) : '');
        $this->redirect($redirect);
    }

    public function updateAltText(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/media');
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $collectionKey = trim((string) ($_POST['collection_key'] ?? ''));

        try {
            $this->mediaService->updateAltText($assetId, (string) ($_POST['alt_text'] ?? ''));
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $redirect = '/admin/media' . ($collectionKey !== '' ? '?collection_key=' . urlencode($collectionKey) : '');
            $this->redirect($redirect);
        }

        $this->flash('success', 'Alt text updated.');
        $redirect = '/admin/media' . ($collectionKey !== '' ? '?collection_key=' . urlencode($collectionKey) : '');
        $this->redirect($redirect);
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/media');
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $collectionKey = trim((string) ($_POST['collection_key'] ?? ''));

        try {
            $this->mediaService->deleteAsset($assetId);
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $redirect = '/admin/media' . ($collectionKey !== '' ? '?collection_key=' . urlencode($collectionKey) : '');
            $this->redirect($redirect);
        }

        $this->flash('success', 'Media asset deleted.');
        $redirect = '/admin/media' . ($collectionKey !== '' ? '?collection_key=' . urlencode($collectionKey) : '');
        $this->redirect($redirect);
    }
}
