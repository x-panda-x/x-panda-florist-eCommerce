<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\AdminSelectionService;
use App\Services\CMSService;
use App\Services\HomepageSectionService;
use App\Services\MediaService;

final class HomepageController extends BaseAdminController
{
    private const HERO_COLLECTION_KEY = 'homepage-hero';

    private AdminSelectionService $adminSelectionService;
    private CMSService $cmsService;
    private HomepageSectionService $homepageSectionService;
    private MediaService $mediaService;

    /**
     * @var array<int, string>
     */
    private array $sectionKeys = [
        'hero',
        'quick_links',
        'feature_intro',
        'features',
        'trust',
        'newsletter',
        'seo',
    ];

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->adminSelectionService = new AdminSelectionService($app);
        $this->cmsService = new CMSService($app);
        $this->homepageSectionService = new HomepageSectionService($app);
        $this->mediaService = new MediaService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $this->cmsService->ensureHomepageFoundation();
        $heroBlock = $this->cmsService->getHomepageHeroBlock(false);
        $currentHeroAsset = is_array($heroBlock['media_asset'] ?? null) ? $heroBlock['media_asset'] : null;
        $heroAssets = $this->heroAssetsForManager($currentHeroAsset);

        return $this->renderAdmin('admin-homepage-sections', [
            'pageTitle' => 'Homepage',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'formData' => $this->formDataFromBlocks($this->cmsService->getHomepageBlocks(false)),
            'assets' => $this->mediaService->listAssets(null, 250),
            'currentHeroAsset' => $currentHeroAsset,
            'heroAssets' => $heroAssets,
            'homepageProductSections' => $this->homepageSectionService->listAdminSections(),
            'homepageProductOptions' => $this->homepageSectionService->listProductOptions(),
            'homepageSectionPresetOptions' => $this->adminSelectionService->homepageSectionPresetOptions(),
            'linkOptions' => $this->adminSelectionService->linkOptions(),
        ]);
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/homepage');
        }

        $validationError = $this->validateInput($_POST);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/homepage');
        }

        try {
            $this->cmsService->saveHomepageBlocks($_POST);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update homepage sections.');
            $this->redirect('/admin/homepage');
        }

        $this->flash('success', 'Advanced homepage content blocks updated. Product slider changes require "Save Product Sliders".');
        $this->redirect('/admin/homepage');
    }

    public function updateProductSections(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/homepage#homepage-product-sections');
        }

        try {
            $this->homepageSectionService->saveFromAdminPayload($_POST);
        } catch (\InvalidArgumentException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/admin/homepage#homepage-product-sections');
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update homepage product sections.');
            $this->redirect('/admin/homepage#homepage-product-sections');
        }

        $this->flash('success', 'Homepage product sections updated.');
        $this->redirect('/admin/homepage#homepage-product-sections');
    }

    public function uploadHero(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/homepage');
        }

        $file = $_FILES['hero_image'] ?? null;

        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Choose a valid hero image to upload.');
            $this->redirect('/admin/homepage');
        }

        try {
            $asset = $this->mediaService->uploadAsset(
                $file,
                self::HERO_COLLECTION_KEY,
                trim((string) ($_POST['hero_alt_text'] ?? 'Homepage hero image')) ?: null,
                !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null
            );
            $this->cmsService->setHomepageHeroMediaAssetId((int) ($asset['id'] ?? 0));
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to upload and activate the new hero image.');
            $this->redirect('/admin/homepage');
        }

        $this->flash('success', 'New homepage hero uploaded and activated.');
        $this->redirect('/admin/homepage');
    }

    public function activateHero(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/homepage');
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);

        if ($assetId <= 0 || $this->mediaService->findAssetById($assetId) === null) {
            $this->flash('error', 'Choose a valid hero image.');
            $this->redirect('/admin/homepage');
        }

        try {
            $this->cmsService->setHomepageHeroMediaAssetId($assetId);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to activate that hero image.');
            $this->redirect('/admin/homepage');
        }

        $this->flash('success', 'Homepage hero image updated.');
        $this->redirect('/admin/homepage');
    }

    public function deleteHero(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/homepage');
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $currentHeroAssetId = (int) (($this->cmsService->getHomepageHeroBlock(false)['media_asset_id'] ?? 0));

        if ($assetId <= 0 || $this->mediaService->findAssetById($assetId) === null) {
            $this->flash('error', 'Hero image not found.');
            $this->redirect('/admin/homepage');
        }

        if ($assetId === $currentHeroAssetId) {
            $this->flash('error', 'The active homepage hero cannot be deleted. Activate a different hero image first.');
            $this->redirect('/admin/homepage');
        }

        try {
            $this->mediaService->deleteAsset($assetId);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to delete that hero image because it is still in use elsewhere.');
            $this->redirect('/admin/homepage');
        }

        $this->flash('success', 'Unused hero image deleted.');
        $this->redirect('/admin/homepage');
    }

    /**
     * @param array<string, array<string, mixed>> $blocks
     * @return array<string, mixed>
     */
    private function formDataFromBlocks(array $blocks): array
    {
        $formData = [];
        $map = [
            'hero' => 'home.hero',
            'quick_links' => 'home.quick-links',
            'feature_intro' => 'home.feature-intro',
            'features' => 'home.features',
            'trust' => 'home.trust',
            'newsletter' => 'home.newsletter',
            'seo' => 'home.seo',
        ];

        foreach ($map as $prefix => $blockKey) {
            $block = $blocks[$blockKey] ?? [];
            $formData[$prefix . '_subheading'] = (string) ($block['subheading'] ?? '');
            $formData[$prefix . '_heading'] = (string) ($block['heading'] ?? '');
            $formData[$prefix . '_body_text'] = (string) ($block['body_text'] ?? '');
            $formData[$prefix . '_cta_label'] = (string) ($block['cta_label'] ?? '');
            $formData[$prefix . '_cta_url'] = (string) ($block['cta_url'] ?? '');
            $formData[$prefix . '_media_asset_id'] = (int) ($block['media_asset_id'] ?? 0);
            $formData[$prefix . '_is_enabled'] = !empty($block['is_enabled']) ? 1 : 0;
            $formData[$prefix . '_sort_order'] = (int) ($block['sort_order'] ?? 0);
            $formData[$prefix . '_items_text'] = $this->itemsToText($block['items'] ?? []);
        }

        return $formData;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function itemsToText(array $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $bodyText = trim((string) ($item['body_text'] ?? ''));
            $ctaUrl = trim((string) ($item['cta_url'] ?? ''));

            if ($title === '') {
                continue;
            }

            if ($bodyText !== '' && $ctaUrl !== '') {
                $lines[] = $title . '|' . $bodyText . '|' . $ctaUrl;
                continue;
            }

            if ($bodyText !== '') {
                $lines[] = $title . '|' . $bodyText;
                continue;
            }

            if ($ctaUrl !== '') {
                $lines[] = $title . '|' . $ctaUrl;
                continue;
            }

            $lines[] = $title;
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validateInput(array $input): ?string
    {
        foreach ($this->sectionKeys as $sectionKey) {
            $sortOrder = (string) ($input[$sectionKey . '_sort_order'] ?? '0');
            $ctaUrl = trim((string) ($input[$sectionKey . '_cta_url'] ?? ''));

            if (!is_numeric($sortOrder) || (int) $sortOrder < 0) {
                return 'Each homepage section sort order must be a non-negative whole number.';
            }

            if ($ctaUrl !== '' && strpos($ctaUrl, '/') !== 0 && filter_var($ctaUrl, FILTER_VALIDATE_URL) === false) {
                return 'Homepage CTA URLs must be valid relative or absolute URLs.';
            }

            $mediaAssetId = (int) ($input[$sectionKey . '_media_asset_id'] ?? 0);

            if ($mediaAssetId > 0 && $this->mediaService->findAssetById($mediaAssetId) === null) {
                return 'Choose a valid media asset for each homepage section.';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $currentHeroAsset
     * @return array<int, array<string, mixed>>
     */
    private function heroAssetsForManager(?array $currentHeroAsset): array
    {
        $heroAssets = $this->mediaService->listAssets(self::HERO_COLLECTION_KEY, 100);
        $byId = [];

        foreach ($heroAssets as $asset) {
            $byId[(int) ($asset['id'] ?? 0)] = $asset;
        }

        if (is_array($currentHeroAsset ?? null) && !empty($currentHeroAsset['id'])) {
            $byId[(int) $currentHeroAsset['id']] = $currentHeroAsset;
        }

        return array_values($byId);
    }
}
