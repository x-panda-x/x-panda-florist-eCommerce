<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\AdminSelectionService;
use App\Services\CMSService;

final class FooterSeoController extends BaseAdminController
{
    private AdminSelectionService $adminSelectionService;
    private CMSService $cmsService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->adminSelectionService = new AdminSelectionService($app);
        $this->cmsService = new CMSService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $this->cmsService->ensureGlobalChromeFoundation();
        $blocks = $this->cmsService->getFooterBlocks(false);

        return $this->renderAdmin('admin-footer-seo', [
            'pageTitle' => 'Footer Content',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'formData' => $this->formDataFromBlocks($blocks),
            'linkOptions' => $this->adminSelectionService->linkOptions(),
        ]);
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/footer-seo');
        }

        try {
            $this->cmsService->saveFooterBlocks($_POST);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update footer content.');
            $this->redirect('/admin/footer-seo');
        }

        $this->flash('success', 'Footer content updated.');
        $this->redirect('/admin/footer-seo');
    }

    /**
     * @param array<string, array<string, mixed>> $blocks
     * @return array<string, string>
     */
    private function formDataFromBlocks(array $blocks): array
    {
        return [
            'about_heading' => (string) ($blocks['global.footer.about']['heading'] ?? ''),
            'about_body_text' => (string) ($blocks['global.footer.about']['body_text'] ?? ''),
            'about_items_text' => $this->itemsToText($blocks['global.footer.about']['items'] ?? []),
            'shop_heading' => (string) ($blocks['global.footer.shop']['heading'] ?? ''),
            'shop_items_text' => $this->itemsToText($blocks['global.footer.shop']['items'] ?? []),
            'service_heading' => (string) ($blocks['global.footer.service']['heading'] ?? ''),
            'service_items_text' => $this->itemsToText($blocks['global.footer.service']['items'] ?? []),
            'business_heading' => (string) ($blocks['global.footer.business']['heading'] ?? ''),
            'business_items_text' => $this->itemsToText($blocks['global.footer.business']['items'] ?? []),
            'bottom_body_text' => (string) ($blocks['global.footer.bottom']['body_text'] ?? ''),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function itemsToText(array $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['cta_url'] ?? ''));

            if ($title === '') {
                continue;
            }

            $lines[] = $url !== '' ? $title . '|' . $url : $title;
        }

        return implode(PHP_EOL, $lines);
    }
}
