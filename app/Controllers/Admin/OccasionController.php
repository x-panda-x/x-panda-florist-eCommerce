<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\ProductService;

final class OccasionController extends BaseAdminController
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

        return $this->renderAdmin('admin-taxonomy-index', [
            'pageTitle' => 'Occasions',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'items' => $this->productService->listOccasions(),
            'itemLabelPlural' => 'Occasions',
            'itemLabelSingular' => 'Occasion',
            'createPath' => '/admin/occasions/create',
            'editBasePath' => '/admin/occasions/edit',
            'deletePath' => '/admin/occasions/delete',
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderForm([
            'pageTitle' => 'Create Occasion',
            'formAction' => '/admin/occasions',
            'indexPath' => '/admin/occasions',
            'itemLabelSingular' => 'Occasion',
            'taxonomy' => $this->emptyTaxonomy(),
            'formMode' => 'create',
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();

        $occasionId = (int) ($_GET['id'] ?? 0);
        $occasion = $this->productService->findOccasionById($occasionId);

        if ($occasion === null) {
            $this->flash('error', 'Occasion not found.');
            $this->redirect('/admin/occasions');
        }

        return $this->renderForm([
            'pageTitle' => 'Edit Occasion',
            'formAction' => '/admin/occasions/update',
            'indexPath' => '/admin/occasions',
            'itemLabelSingular' => 'Occasion',
            'taxonomy' => $occasion,
            'taxonomyId' => $occasionId,
            'formMode' => 'edit',
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/occasions/create');
        }

        $occasion = $this->normalizeTaxonomy($_POST);
        $validationError = $this->validateTaxonomy($occasion);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/occasions/create');
        }

        try {
            $this->productService->createOccasion($occasion);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save occasion. Check for a duplicate slug and try again.');
            $this->redirect('/admin/occasions/create');
        }

        $this->flash('success', 'Occasion created.');
        $this->redirect('/admin/occasions');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/occasions');
        }

        $occasionId = (int) ($_POST['id'] ?? 0);

        if ($occasionId <= 0 || $this->productService->findOccasionById($occasionId) === null) {
            $this->flash('error', 'Occasion not found.');
            $this->redirect('/admin/occasions');
        }

        $occasion = $this->normalizeTaxonomy($_POST);
        $validationError = $this->validateTaxonomy($occasion);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/occasions/edit?id=' . $occasionId);
        }

        try {
            $this->productService->updateOccasion($occasionId, $occasion);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update occasion. Check for a duplicate slug and try again.');
            $this->redirect('/admin/occasions/edit?id=' . $occasionId);
        }

        $this->flash('success', 'Occasion updated.');
        $this->redirect('/admin/occasions');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/occasions');
        }

        $occasionId = (int) ($_POST['id'] ?? 0);

        if ($occasionId <= 0 || $this->productService->findOccasionById($occasionId) === null) {
            $this->flash('error', 'Occasion not found.');
            $this->redirect('/admin/occasions');
        }

        try {
            $this->productService->deleteOccasion($occasionId);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to delete occasion. It may still be assigned to products.');
            $this->redirect('/admin/occasions');
        }

        $this->flash('success', 'Occasion deleted.');
        $this->redirect('/admin/occasions');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function normalizeTaxonomy(array $input): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'slug' => trim((string) ($input['slug'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $taxonomy
     */
    private function validateTaxonomy(array $taxonomy): ?string
    {
        if ($taxonomy['name'] === '') {
            return 'Name is required.';
        }

        if ($taxonomy['slug'] === '') {
            return 'Slug is required.';
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $taxonomy['slug']) !== 1) {
            return 'Slug must use lowercase letters, numbers, and hyphens only.';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function emptyTaxonomy(): array
    {
        return [
            'name' => '',
            'slug' => '',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(array $data): string
    {
        return $this->renderAdmin('admin-taxonomy-form', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }
}
