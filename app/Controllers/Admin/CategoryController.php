<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\ProductService;

final class CategoryController extends BaseAdminController
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
            'pageTitle' => 'Categories',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'items' => $this->productService->listCategories(),
            'itemLabelPlural' => 'Categories',
            'itemLabelSingular' => 'Category',
            'createPath' => '/admin/categories/create',
            'editBasePath' => '/admin/categories/edit',
            'deletePath' => '/admin/categories/delete',
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderForm([
            'pageTitle' => 'Create Category',
            'formAction' => '/admin/categories',
            'indexPath' => '/admin/categories',
            'itemLabelSingular' => 'Category',
            'taxonomy' => $this->emptyTaxonomy(),
            'formMode' => 'create',
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();

        $categoryId = (int) ($_GET['id'] ?? 0);
        $category = $this->productService->findCategoryById($categoryId);

        if ($category === null) {
            $this->flash('error', 'Category not found.');
            $this->redirect('/admin/categories');
        }

        return $this->renderForm([
            'pageTitle' => 'Edit Category',
            'formAction' => '/admin/categories/update',
            'indexPath' => '/admin/categories',
            'itemLabelSingular' => 'Category',
            'taxonomy' => $category,
            'taxonomyId' => $categoryId,
            'formMode' => 'edit',
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/categories/create');
        }

        $category = $this->normalizeTaxonomy($_POST);
        $validationError = $this->validateTaxonomy($category);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/categories/create');
        }

        try {
            $this->productService->createCategory($category);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save category. Check for a duplicate slug and try again.');
            $this->redirect('/admin/categories/create');
        }

        $this->flash('success', 'Category created.');
        $this->redirect('/admin/categories');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/categories');
        }

        $categoryId = (int) ($_POST['id'] ?? 0);

        if ($categoryId <= 0 || $this->productService->findCategoryById($categoryId) === null) {
            $this->flash('error', 'Category not found.');
            $this->redirect('/admin/categories');
        }

        $category = $this->normalizeTaxonomy($_POST);
        $validationError = $this->validateTaxonomy($category);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/categories/edit?id=' . $categoryId);
        }

        try {
            $this->productService->updateCategory($categoryId, $category);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update category. Check for a duplicate slug and try again.');
            $this->redirect('/admin/categories/edit?id=' . $categoryId);
        }

        $this->flash('success', 'Category updated.');
        $this->redirect('/admin/categories');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/categories');
        }

        $categoryId = (int) ($_POST['id'] ?? 0);

        if ($categoryId <= 0 || $this->productService->findCategoryById($categoryId) === null) {
            $this->flash('error', 'Category not found.');
            $this->redirect('/admin/categories');
        }

        try {
            $this->productService->deleteCategory($categoryId);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to delete category. It may still be assigned to products.');
            $this->redirect('/admin/categories');
        }

        $this->flash('success', 'Category deleted.');
        $this->redirect('/admin/categories');
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
