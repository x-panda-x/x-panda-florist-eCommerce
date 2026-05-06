<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\AdminSelectionService;
use App\Services\CMSService;
use App\Services\MediaService;

final class PublicPagesController extends BaseAdminController
{
    private AdminSelectionService $adminSelectionService;
    private CMSService $cmsService;
    private MediaService $mediaService;

    /**
     * @var array<int, string>
     */
    private array $sectionKeys = [
        'contact_hero',
        'contact_support',
        'order_status_intro',
        'order_status_empty',
        'checkout_help',
        'payment_help',
        'order_confirmation_help',
        'best_sellers_intro',
        'occasions_intro',
        'search_intro',
        'search_empty',
        'product_detail_helper',
        'product_detail_related',
    ];

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
        $this->cmsService->ensurePublicPageFoundation();

        return $this->renderAdmin('admin-public-pages', [
            'pageTitle' => 'Public Pages',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'formData' => $this->formDataFromBlocks($this->cmsService->getPublicPageBlocks(false)),
            'assets' => $this->mediaService->listAssets(null, 250),
            'linkOptions' => $this->adminSelectionService->linkOptions(),
        ]);
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/public-pages');
        }

        $validationError = $this->validateInput($_POST);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/public-pages');
        }

        try {
            $this->cmsService->savePublicPageBlocks($_POST);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update public page content.');
            $this->redirect('/admin/public-pages');
        }

        $this->flash('success', 'Public page content updated.');
        $this->redirect('/admin/public-pages');
    }

    /**
     * @param array<string, array<string, mixed>> $blocks
     * @return array<string, mixed>
     */
    private function formDataFromBlocks(array $blocks): array
    {
        $formData = [];
        $map = [
            'contact_hero' => 'page.contact.hero',
            'contact_support' => 'page.contact.support',
            'order_status_intro' => 'page.order-status.intro',
            'order_status_empty' => 'page.order-status.empty',
            'checkout_help' => 'page.checkout.help',
            'payment_help' => 'page.payment.help',
            'order_confirmation_help' => 'page.order-confirmation.help',
            'best_sellers_intro' => 'page.best-sellers.intro',
            'occasions_intro' => 'page.occasions.intro',
            'search_intro' => 'page.search.intro',
            'search_empty' => 'page.search.empty',
            'product_detail_helper' => 'page.product-detail.helper',
            'product_detail_related' => 'page.product-detail.related',
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
                return 'Each public page section sort order must be a non-negative whole number.';
            }

            if ($ctaUrl !== '' && strpos($ctaUrl, '/') !== 0 && filter_var($ctaUrl, FILTER_VALIDATE_URL) === false) {
                return 'Public page CTA URLs must be valid relative or absolute URLs.';
            }

            $mediaAssetId = (int) ($input[$sectionKey . '_media_asset_id'] ?? 0);

            if ($mediaAssetId > 0 && $this->mediaService->findAssetById($mediaAssetId) === null) {
                return 'Choose a valid media asset for each public page section.';
            }
        }

        return null;
    }
}
