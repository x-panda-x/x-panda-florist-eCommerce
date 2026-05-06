<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\AdminSelectionService;
use App\Services\CMSService;
use App\Services\MediaService;

final class BannerController extends BaseAdminController
{
    private AdminSelectionService $adminSelectionService;
    private CMSService $cmsService;
    private MediaService $mediaService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->adminSelectionService = new AdminSelectionService($app);
        $this->cmsService = new CMSService($app);
        $this->mediaService = new MediaService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $this->cmsService->ensureGlobalChromeFoundation();

        return $this->renderAdmin('admin-banners', [
            'pageTitle' => 'Banners',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'banners' => $this->cmsService->listAllBanners(),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderForm([
            'pageTitle' => 'Create Banner',
            'formAction' => '/admin/banners',
            'formMode' => 'create',
            'banner' => $this->emptyBanner(),
            'assets' => $this->mediaService->listAssets(),
            'linkOptions' => $this->adminSelectionService->linkOptions(),
            'pageKeyOptions' => $this->adminSelectionService->pageKeyOptions(),
            'bannerPlacementOptions' => $this->adminSelectionService->bannerPlacementOptions(),
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();
        $bannerId = (int) ($_GET['id'] ?? 0);
        $banner = $this->cmsService->findBannerById($bannerId);

        if ($banner === null) {
            $this->flash('error', 'Banner not found.');
            $this->redirect('/admin/banners');
        }

        return $this->renderForm([
            'pageTitle' => 'Edit Banner',
            'formAction' => '/admin/banners/update',
            'formMode' => 'edit',
            'banner' => $banner,
            'bannerId' => $bannerId,
            'assets' => $this->mediaService->listAssets(),
            'linkOptions' => $this->adminSelectionService->linkOptions(),
            'pageKeyOptions' => $this->adminSelectionService->pageKeyOptions(),
            'bannerPlacementOptions' => $this->adminSelectionService->bannerPlacementOptions(),
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/banners/create');
        }

        $banner = $this->normalizeBanner($_POST);
        $validationError = $this->validateBanner($banner);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/banners/create');
        }

        try {
            $this->cmsService->createBanner($banner);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save banner. Check the key and try again.');
            $this->redirect('/admin/banners/create');
        }

        $this->flash('success', 'Banner created.');
        $this->redirect('/admin/banners');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/banners');
        }

        $bannerId = (int) ($_POST['id'] ?? 0);
        $banner = $this->normalizeBanner($_POST);
        $validationError = $this->validateBanner($banner);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/banners/edit?id=' . $bannerId);
        }

        try {
            $this->cmsService->updateBanner($bannerId, $banner);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update banner. Check the key and try again.');
            $this->redirect('/admin/banners/edit?id=' . $bannerId);
        }

        $this->flash('success', 'Banner updated.');
        $this->redirect('/admin/banners');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/banners');
        }

        try {
            $this->cmsService->deleteBanner((int) ($_POST['id'] ?? 0));
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/admin/banners');
        }

        $this->flash('success', 'Banner deleted.');
        $this->redirect('/admin/banners');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeBanner(array $input): array
    {
        return [
            'banner_key' => trim((string) ($input['banner_key'] ?? '')),
            'page_key' => trim((string) ($input['page_key'] ?? 'global')),
            'placement' => trim((string) ($input['placement'] ?? 'promo_strip')),
            'title' => trim((string) ($input['title'] ?? '')),
            'subtitle' => trim((string) ($input['subtitle'] ?? '')),
            'body_text' => trim((string) ($input['body_text'] ?? '')),
            'cta_label' => trim((string) ($input['cta_label'] ?? '')),
            'cta_url' => trim((string) ($input['cta_url'] ?? '')),
            'media_asset_id' => (int) ($input['media_asset_id'] ?? 0),
            'is_enabled' => isset($input['is_enabled']) ? 1 : 0,
            'starts_at' => $this->normalizeDateTimeInput((string) ($input['starts_at'] ?? '')),
            'ends_at' => $this->normalizeDateTimeInput((string) ($input['ends_at'] ?? '')),
            'sort_order' => trim((string) ($input['sort_order'] ?? '0')),
        ];
    }

    /**
     * @param array<string, mixed> $banner
     */
    private function validateBanner(array $banner): ?string
    {
        if ((string) $banner['banner_key'] === '') {
            return 'Banner key is required.';
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $banner['banner_key']) !== 1) {
            return 'Banner key must use lowercase letters, numbers, and hyphens only.';
        }

        if ((string) $banner['placement'] === '') {
            return 'Placement is required.';
        }

        if ((string) $banner['cta_url'] !== '' && strpos((string) $banner['cta_url'], '/') !== 0 && filter_var($banner['cta_url'], FILTER_VALIDATE_URL) === false) {
            return 'CTA URL must be a valid relative or absolute URL.';
        }

        if (!is_numeric((string) $banner['sort_order']) || (int) $banner['sort_order'] < 0) {
            return 'Sort order must be a non-negative whole number.';
        }

        if (
            !empty($banner['starts_at'])
            && !empty($banner['ends_at'])
            && strtotime((string) $banner['ends_at']) < strtotime((string) $banner['starts_at'])
        ) {
            return 'End date must be later than the start date.';
        }

        return null;
    }

    private function normalizeDateTimeInput(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        if (!$dateTime instanceof \DateTimeImmutable || $dateTime->format('Y-m-d\TH:i') !== $value) {
            return null;
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBanner(): array
    {
        return [
            'banner_key' => '',
            'page_key' => 'global',
            'placement' => 'promo_strip',
            'title' => '',
            'subtitle' => '',
            'body_text' => '',
            'cta_label' => '',
            'cta_url' => '',
            'media_asset_id' => 0,
            'is_enabled' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 10,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(array $data): string
    {
        return $this->renderAdmin('admin-banner-form', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }
}
