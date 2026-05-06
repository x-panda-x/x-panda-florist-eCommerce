<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\ProductService;

final class ProductController extends BaseAdminController
{
    private const FIXED_VARIANTS = [
        'standard' => ['name' => 'Standard', 'price_modifier' => '0.00', 'sort_order' => '1'],
        'deluxe' => ['name' => 'Deluxe', 'price_modifier' => '15.00', 'sort_order' => '2'],
        'premium' => ['name' => 'Premium', 'price_modifier' => '30.00', 'sort_order' => '3'],
    ];

    private ProductService $productService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->productService = new ProductService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-products', [
            'pageTitle' => 'Products',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'products' => $this->productService->listProducts(),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderFormPage([
            'pageTitle' => 'Create Product',
            'formAction' => '/admin/products',
            'formMode' => 'create',
            'product' => $this->emptyProduct(),
            'variants' => $this->buildFixedChoiceVariants(),
            'legacyVariantNames' => [],
            'categories' => $this->productService->listCategories(),
            'occasions' => $this->productService->listOccasions(),
            'availableAddons' => $this->productService->listAddons(),
            'availableRelatedProducts' => $this->productService->listProducts(),
            'selectedCategoryIds' => [],
            'selectedOccasionIds' => [],
            'selectedAddonIds' => [],
            'selectedRelatedProductIds' => [],
            'images' => [],
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();

        $productId = (int) ($_GET['id'] ?? 0);
        $product = $this->productService->findProductById($productId);

        if ($product === null) {
            $this->flash('error', 'Product not found.');
            $this->redirect('/admin/products');
        }

        $storedVariants = $this->productService->listVariantsByProductId($productId);
        $variants = $this->buildFixedChoiceVariants($storedVariants);
        $legacyVariantNames = $this->extractLegacyVariantNames($storedVariants);

        return $this->renderFormPage([
            'pageTitle' => 'Edit Product',
            'formAction' => '/admin/products/update',
            'formMode' => 'edit',
            'product' => $product,
            'productId' => $productId,
            'variants' => $variants,
            'legacyVariantNames' => $legacyVariantNames,
            'categories' => $this->productService->listCategories(),
            'occasions' => $this->productService->listOccasions(),
            'availableAddons' => $this->productService->listAddons(),
            'availableRelatedProducts' => $this->productService->listProductsExcludingId($productId),
            'selectedCategoryIds' => $this->productService->listCategoryIdsByProductId($productId),
            'selectedOccasionIds' => $this->productService->listOccasionIdsByProductId($productId),
            'selectedAddonIds' => $this->productService->listAddonIdsByProductId($productId),
            'selectedRelatedProductIds' => $this->productService->listRelatedProductIdsByProductId($productId),
            'images' => $this->productService->listImagesByProductId($productId),
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/products/create');
        }

        $product = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'base_price' => trim((string) ($_POST['base_price'] ?? '0')),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ];
        $variants = $this->normalizeFixedVariants(
            $_POST['variant_enabled'] ?? [],
            $_POST['variant_price_modifier'] ?? [],
            $_POST['variant_sort_order'] ?? [],
            $_POST['variant_name'] ?? []
        );
        $categoryIds = $this->normalizeIds($_POST['category_ids'] ?? []);
        $occasionIds = $this->normalizeIds($_POST['occasion_ids'] ?? []);
        $addonIds = $this->normalizeIds($_POST['addon_ids'] ?? []);
        $relatedProductIds = $this->normalizeIds($_POST['related_product_ids'] ?? []);
        $uploadedImages = $this->normalizeUploadedImages($_FILES['images'] ?? null);
        $validationError = $this->validateProduct($product);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/products/create');
        }

        $variantValidationError = $this->validateVariants($variants);

        if ($variantValidationError !== null) {
            $this->flash('error', $variantValidationError);
            $this->redirect('/admin/products/create');
        }

        $imageValidationError = $this->validateUploadedImages($uploadedImages);

        if ($imageValidationError !== null) {
            $this->flash('error', $imageValidationError);
            $this->redirect('/admin/products/create');
        }

        try {
            $productId = $this->productService->createProductWithVariants(
                $product,
                $variants,
                $categoryIds,
                $occasionIds,
                $addonIds,
                $relatedProductIds
            );
            $this->productService->addProductImages($productId, $uploadedImages);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save product. Check for a duplicate slug or invalid image and try again.');
            $this->redirect('/admin/products/create');
        }

        $this->flash('success', 'Product created.');
        $this->redirect('/admin/products');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/products');
        }

        $productId = (int) ($_POST['id'] ?? 0);

        if ($productId <= 0 || $this->productService->findProductById($productId) === null) {
            $this->flash('error', 'Product not found.');
            $this->redirect('/admin/products');
        }

        $product = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'base_price' => trim((string) ($_POST['base_price'] ?? '0')),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ];
        $variants = $this->normalizeFixedVariants(
            $_POST['variant_enabled'] ?? [],
            $_POST['variant_price_modifier'] ?? [],
            $_POST['variant_sort_order'] ?? [],
            $_POST['variant_name'] ?? []
        );
        $categoryIds = $this->normalizeIds($_POST['category_ids'] ?? []);
        $occasionIds = $this->normalizeIds($_POST['occasion_ids'] ?? []);
        $addonIds = $this->normalizeIds($_POST['addon_ids'] ?? []);
        $relatedProductIds = $this->normalizeIds($_POST['related_product_ids'] ?? []);
        $uploadedImages = $this->normalizeUploadedImages($_FILES['images'] ?? null);
        $existingImageIds = $this->normalizeIds($_POST['existing_image_id'] ?? []);
        $existingImageSortOrders = $this->normalizeImageSortOrders($_POST['existing_image_sort_order'] ?? []);
        $removeImageIds = $this->normalizeIds($_POST['remove_image_ids'] ?? []);

        $validationError = $this->validateProduct($product);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/products/edit?id=' . $productId);
        }

        $variantValidationError = $this->validateVariants($variants);

        if ($variantValidationError !== null) {
            $this->flash('error', $variantValidationError);
            $this->redirect('/admin/products/edit?id=' . $productId);
        }

        $imageValidationError = $this->validateUploadedImages($uploadedImages);

        if ($imageValidationError !== null) {
            $this->flash('error', $imageValidationError);
            $this->redirect('/admin/products/edit?id=' . $productId);
        }

        try {
            $this->productService->updateProductWithVariants(
                $productId,
                $product,
                $variants,
                $categoryIds,
                $occasionIds,
                $addonIds,
                $relatedProductIds
            );
            $this->productService->updateProductImageSortOrders($productId, $existingImageIds, $existingImageSortOrders);

            foreach ($removeImageIds as $imageId) {
                $this->productService->deleteProductImage($productId, $imageId);
            }

            $this->productService->addProductImages($productId, $uploadedImages);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update product. ' . $this->describeProductSaveError($exception));
            $this->redirect('/admin/products/edit?id=' . $productId);
        }

        $this->flash('success', 'Product updated.');
        $this->redirect('/admin/products');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/products');
        }

        $productId = (int) ($_POST['id'] ?? 0);

        if ($productId <= 0 || $this->productService->findProductById($productId) === null) {
            $this->flash('error', 'Product not found.');
            $this->redirect('/admin/products');
        }

        try {
            $this->productService->deleteProduct($productId);
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/admin/products');
        }

        $this->flash('success', 'Product deleted.');
        $this->redirect('/admin/products');
    }

    /**
     * @param array<string, mixed> $product
     */
    private function validateProduct(array $product): ?string
    {
        if ((string) $product['name'] === '') {
            return 'Product name is required.';
        }

        if ((string) $product['slug'] === '') {
            return 'Product slug is required.';
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $product['slug']) !== 1) {
            return 'Product slug must use lowercase letters, numbers, and hyphens only.';
        }

        if (!is_numeric((string) $product['base_price']) || (float) $product['base_price'] < 0) {
            return 'Base price must be a valid non-negative number.';
        }

        return null;
    }

    /**
     * @param mixed $names
     * @param mixed $modifiers
     * @param mixed $sortOrders
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFixedVariants(
        mixed $enabledValues,
        mixed $modifiers,
        mixed $sortOrders,
        mixed $legacyNames = []
    ): array
    {
        $enabledValues = is_array($enabledValues) ? $enabledValues : [];
        $modifiers = is_array($modifiers) ? $modifiers : [];
        $sortOrders = is_array($sortOrders) ? $sortOrders : [];
        $variants = [];

        foreach (self::FIXED_VARIANTS as $key => $definition) {
            if (!isset($enabledValues[$key])) {
                continue;
            }

            $variants[] = [
                'name' => (string) $definition['name'],
                'price_modifier' => trim((string) ($modifiers[$key] ?? $definition['price_modifier'])),
                'sort_order' => trim((string) ($sortOrders[$key] ?? $definition['sort_order'])),
            ];
        }

        if ($variants !== []) {
            return $variants;
        }

        return $this->normalizeLegacyVariants($legacyNames, $modifiers, $sortOrders);
    }

    /**
     * @param mixed $names
     * @param mixed $modifiers
     * @param mixed $sortOrders
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLegacyVariants(mixed $names, mixed $modifiers, mixed $sortOrders): array
    {
        $names = is_array($names) ? $names : [];
        $modifiers = is_array($modifiers) ? $modifiers : [];
        $sortOrders = is_array($sortOrders) ? $sortOrders : [];
        $rowCount = max(count($names), count($modifiers), count($sortOrders));
        $variants = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $name = trim((string) ($names[$index] ?? ''));

            if ($name === '') {
                continue;
            }

            $variants[] = [
                'name' => $name,
                'price_modifier' => trim((string) ($modifiers[$index] ?? '0')),
                'sort_order' => trim((string) ($sortOrders[$index] ?? (string) ($index + 1))),
            ];
        }

        return $variants;
    }

    /**
     * @param array<int, array<string, mixed>> $variants
     */
    private function validateVariants(array $variants): ?string
    {
        if ($variants === []) {
            return 'At least one product variant is required.';
        }

        foreach ($variants as $variant) {
            if ((string) $variant['name'] === '') {
                return 'Each saved variant must have a name.';
            }

            if (!is_numeric((string) $variant['price_modifier'])) {
                return 'Each variant price modifier must be a valid number.';
            }

            if (!ctype_digit((string) $variant['sort_order'])) {
                return 'Each variant sort order must be a non-negative whole number.';
            }
        }

        return null;
    }

    /**
     * @param mixed $values
     * @return array<int, int>
     */
    private function normalizeIds(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            $id = (int) $value;

            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param mixed $values
     * @return array<int, int>
     */
    private function normalizeImageSortOrders(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $index => $value) {
            $normalized[(int) $index] = max(0, (int) $value);
        }

        return $normalized;
    }

    /**
     * @param mixed $files
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUploadedImages(mixed $files): array
    {
        if (!is_array($files) || !isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])) {
            return [];
        }

        $names = is_array($files['name']) ? $files['name'] : [];
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [];
        $errors = is_array($files['error']) ? $files['error'] : [];
        $sizes = is_array($files['size']) ? $files['size'] : [];
        $normalized = [];
        $count = max(count($names), count($tmpNames), count($errors), count($sizes));

        for ($index = 0; $index < $count; $index++) {
            $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $normalized[] = [
                'name' => (string) ($names[$index] ?? ''),
                'tmp_name' => (string) ($tmpNames[$index] ?? ''),
                'error' => $error,
                'size' => (int) ($sizes[$index] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $files
     */
    private function validateUploadedImages(array $files): ?string
    {
        if ($files === []) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        foreach ($files as $file) {
            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                return 'One or more uploaded images failed to upload.';
            }

            if ((int) $file['size'] <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
                return 'Each uploaded image must be smaller than 5MB.';
            }

            $tmpName = (string) $file['tmp_name'];

            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                return 'One or more uploaded images are invalid.';
            }

            $mimeType = $finfo->file($tmpName);

            if (!is_string($mimeType) || !in_array($mimeType, $allowedMimeTypes, true)) {
                return 'Only JPG, PNG, and WebP images are allowed.';
            }

            $file['mime_type'] = $mimeType;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFormPage(array $data): string
    {
        return $this->renderAdmin('admin-product-create', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyProduct(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'description' => '',
            'base_price' => '',
            'is_featured' => 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFixedChoiceVariants(array $storedVariants = []): array
    {
        $variants = [];
        $storedByKey = [];

        foreach ($storedVariants as $variant) {
            $key = $this->fixedVariantKeyFromName((string) ($variant['name'] ?? ''));

            if ($key === null) {
                continue;
            }

            $storedByKey[$key] = $variant;
        }

        foreach (self::FIXED_VARIANTS as $key => $definition) {
            $storedVariant = $storedByKey[$key] ?? null;
            $variants[] = [
                'key' => $key,
                'name' => (string) $definition['name'],
                'enabled' => $storedVariant !== null || $storedVariants === [],
                'price_modifier' => (string) ($storedVariant['price_modifier'] ?? $definition['price_modifier']),
                'sort_order' => (string) ($storedVariant['sort_order'] ?? $definition['sort_order']),
            ];
        }

        return $variants;
    }

    /**
     * @param array<int, array<string, mixed>> $storedVariants
     * @return array<int, string>
     */
    private function extractLegacyVariantNames(array $storedVariants): array
    {
        $legacy = [];

        foreach ($storedVariants as $variant) {
            $name = trim((string) ($variant['name'] ?? ''));

            if ($name === '' || $this->fixedVariantKeyFromName($name) !== null) {
                continue;
            }

            $legacy[] = $name;
        }

        return $legacy;
    }

    private function fixedVariantKeyFromName(string $name): ?string
    {
        $normalized = strtolower(trim($name));

        foreach (self::FIXED_VARIANTS as $key => $definition) {
            if ($normalized === strtolower((string) $definition['name'])) {
                return $key;
            }
        }

        return null;
    }

    private function describeProductSaveError(\Throwable $exception): string
    {
        $message = strtolower(trim($exception->getMessage()));

        if ($message === '') {
            return 'Check for a duplicate slug, invalid image, or invalid variant values and try again.';
        }

        if (str_contains($message, 'duplicate') || str_contains($message, 'unique') || str_contains($message, 'slug')) {
            return 'The slug is already in use. Choose a different slug and try again.';
        }

        if (str_contains($message, 'image') || str_contains($message, 'upload')) {
            return 'One or more images could not be processed. Check the uploaded files and try again.';
        }

        return 'Check the variant, image, and slug values and try again.';
    }
}
