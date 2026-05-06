<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\ProductService;

final class AddonController extends BaseAdminController
{
    private ProductService $productService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->productService = new ProductService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-addons', [
            'pageTitle' => 'Add-Ons',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'addons' => $this->productService->listAddons(),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderForm([
            'pageTitle' => 'Create Add-On',
            'formAction' => '/admin/addons',
            'addon' => $this->emptyAddon(),
            'formMode' => 'create',
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();

        $addonId = (int) ($_GET['id'] ?? 0);
        $addon = $this->productService->findAddonById($addonId);

        if ($addon === null) {
            $this->flash('error', 'Add-on not found.');
            $this->redirect('/admin/addons');
        }

        return $this->renderForm([
            'pageTitle' => 'Edit Add-On',
            'formAction' => '/admin/addons/update',
            'addon' => $addon,
            'addonId' => $addonId,
            'formMode' => 'edit',
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/addons/create');
        }

        $addon = $this->normalizeAddon($_POST);
        $validationError = $this->validateAddon($addon);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/addons/create');
        }

        try {
            $this->productService->createAddon($addon);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save add-on. Check for a duplicate slug and try again.');
            $this->redirect('/admin/addons/create');
        }

        $this->flash('success', 'Add-on created.');
        $this->redirect('/admin/addons');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/addons');
        }

        $addonId = (int) ($_POST['id'] ?? 0);

        if ($addonId <= 0 || $this->productService->findAddonById($addonId) === null) {
            $this->flash('error', 'Add-on not found.');
            $this->redirect('/admin/addons');
        }

        $addon = $this->normalizeAddon($_POST);
        $validationError = $this->validateAddon($addon);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/addons/edit?id=' . $addonId);
        }

        try {
            $this->productService->updateAddon($addonId, $addon);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update add-on. Check for a duplicate slug and try again.');
            $this->redirect('/admin/addons/edit?id=' . $addonId);
        }

        $this->flash('success', 'Add-on updated.');
        $this->redirect('/admin/addons');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/addons');
        }

        $addonId = (int) ($_POST['id'] ?? 0);

        if ($addonId <= 0 || $this->productService->findAddonById($addonId) === null) {
            $this->flash('error', 'Add-on not found.');
            $this->redirect('/admin/addons');
        }

        try {
            $this->productService->deleteAddon($addonId);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to delete add-on right now.');
            $this->redirect('/admin/addons');
        }

        $this->flash('success', 'Add-on deleted.');
        $this->redirect('/admin/addons');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeAddon(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'price' => trim((string) ($input['price'] ?? '0')),
            'is_active' => isset($input['is_active']) ? 1 : 0,
            'sort_order' => max(0, (int) ($input['sort_order'] ?? 0)),
        ];
    }

    /**
     * @param array<string, mixed> $addon
     */
    private function validateAddon(array $addon): ?string
    {
        if ((string) $addon['name'] === '') {
            return 'Name is required.';
        }

        if ((string) $addon['slug'] === '') {
            return 'Slug is required.';
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $addon['slug']) !== 1) {
            return 'Slug must use lowercase letters, numbers, and hyphens only.';
        }

        if (!is_numeric((string) $addon['price']) || (float) $addon['price'] < 0) {
            return 'Price must be a valid non-negative number.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAddon(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'description' => '',
            'price' => '0.00',
            'is_active' => 1,
            'sort_order' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(array $data): string
    {
        return $this->renderAdmin('admin-addon-form', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }
}
