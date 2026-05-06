<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class AdminSelectionService
{
    private Application $app;
    private ProductService $productService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->productService = new ProductService($app);
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    public function linkOptions(): array
    {
        return $this->filterEmptyGroups([
            [
                'label' => 'Store Pages',
                'options' => [
                    $this->option('Home', '/'),
                    $this->option('Best Sellers', '/best-sellers'),
                    $this->option('All Occasions', '/occasions'),
                    $this->option('Same Day', '/same-day'),
                    $this->option('Search', '/search'),
                    $this->option('Gift Search', '/search?q=gift'),
                    $this->option('Contact', '/contact'),
                    $this->option('Cart', '/cart'),
                    $this->option('Checkout', '/checkout'),
                    $this->option('Order Status', '/order-status'),
                    $this->option('Customer Sign In', '/account/login'),
                    $this->option('Create Account', '/account/register'),
                ],
            ],
            [
                'label' => 'Occasions',
                'options' => array_map(
                    fn (array $occasion): array => $this->option(
                        (string) ($occasion['name'] ?? ''),
                        '/occasions?occasion=' . rawurlencode((string) ($occasion['slug'] ?? ''))
                    ),
                    $this->productService->listOccasions()
                ),
            ],
            [
                'label' => 'Categories',
                'options' => array_map(
                    fn (array $category): array => $this->option(
                        (string) ($category['name'] ?? ''),
                        '/search?category=' . rawurlencode((string) ($category['slug'] ?? ''))
                    ),
                    $this->productService->listCategories()
                ),
            ],
            [
                'label' => 'Products',
                'options' => array_map(
                    fn (array $product): array => $this->option(
                        (string) ($product['name'] ?? ''),
                        '/product?slug=' . rawurlencode((string) ($product['slug'] ?? ''))
                    ),
                    $this->productService->listProducts()
                ),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    public function pageKeyOptions(): array
    {
        $fixed = [
            'global' => 'Global',
            'home' => 'Homepage',
            'contact' => 'Contact',
            'order-status' => 'Order Status',
            'checkout' => 'Checkout',
            'payment' => 'Payment',
            'order-confirmation' => 'Order Confirmation',
            'best-sellers' => 'Best Sellers',
            'occasions' => 'Occasions',
            'search' => 'Search',
            'product-detail' => 'Product Detail',
        ];
        $fixedKeys = array_keys($fixed);
        $existing = $this->distinctValues([
            'SELECT DISTINCT page_key AS value FROM content_blocks WHERE page_key <> ""',
            'SELECT DISTINCT page_key AS value FROM banners WHERE page_key <> ""',
        ]);
        $customExisting = array_values(array_filter(
            $existing,
            static fn (string $value): bool => !in_array($value, $fixedKeys, true)
        ));

        return $this->filterEmptyGroups([
            [
                'label' => 'Known Pages',
                'options' => array_map(
                    fn (string $key, string $label): array => $this->option($label, $key),
                    array_keys($fixed),
                    array_values($fixed)
                ),
            ],
            [
                'label' => 'Existing Custom Page Keys',
                'options' => array_map(
                    fn (string $value): array => $this->option($this->labelizeKey($value), $value),
                    $customExisting
                ),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    public function bannerPlacementOptions(): array
    {
        $fixed = [
            'promo_strip' => 'Promo Strip',
        ];
        $existing = $this->distinctValues([
            'SELECT DISTINCT placement AS value FROM banners WHERE placement <> ""',
        ]);
        $customExisting = array_values(array_filter(
            $existing,
            static fn (string $value): bool => !isset($fixed[$value])
        ));

        return $this->filterEmptyGroups([
            [
                'label' => 'Storefront Placements',
                'options' => array_map(
                    fn (string $key, string $label): array => $this->option($label, $key),
                    array_keys($fixed),
                    array_values($fixed)
                ),
            ],
            [
                'label' => 'Existing Custom Placements',
                'options' => array_map(
                    fn (string $value): array => $this->option($this->labelizeKey($value), $value),
                    $customExisting
                ),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    public function mediaCollectionOptions(): array
    {
        $fixed = [
            'homepage-hero' => 'Homepage Hero Images',
            'homepage-sections' => 'Homepage Section Images',
            'public-pages' => 'Public Page Images',
            'banners' => 'Banner Images',
            'footer' => 'Footer Images',
            'navigation' => 'Navigation Assets',
        ];
        $existing = $this->distinctValues([
            'SELECT DISTINCT collection_key AS value FROM media_assets WHERE collection_key <> ""',
        ]);
        $customExisting = array_values(array_filter(
            $existing,
            static fn (string $value): bool => !isset($fixed[$value])
        ));

        return $this->filterEmptyGroups([
            [
                'label' => 'Recommended Collections',
                'options' => array_map(
                    fn (string $key, string $label): array => $this->option($label, $key),
                    array_keys($fixed),
                    array_values($fixed)
                ),
            ],
            [
                'label' => 'Existing Collections',
                'options' => array_map(
                    fn (string $value): array => $this->option($this->labelizeKey($value), $value),
                    $customExisting
                ),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    public function homepageSectionPresetOptions(): array
    {
        $allProductIds = $this->productIdsBySql(
            'SELECT id
             FROM products
             ORDER BY is_featured DESC, updated_at DESC, id DESC'
        );
        $featuredProductIds = $this->productIdsBySql(
            'SELECT id
             FROM products
             WHERE is_featured = 1
             ORDER BY updated_at DESC, id DESC'
        );
        $generalOptions = [];

        if ($featuredProductIds !== []) {
            $generalOptions[] = $this->homepagePresetOption(
                'Best Sellers',
                'Best Sellers',
                'Collection',
                'View All',
                '/best-sellers',
                $featuredProductIds
            );
        }

        if ($allProductIds !== []) {
            $generalOptions[] = $this->homepagePresetOption(
                'All Products',
                'Featured Flowers',
                'Collection',
                'View All',
                '/search',
                $allProductIds
            );
        }

        $occasionOptions = [];

        foreach ($this->productService->listOccasions() as $occasion) {
            $occasionId = (int) ($occasion['id'] ?? 0);
            $slug = (string) ($occasion['slug'] ?? '');
            $productIds = $this->productIdsBySql(
                'SELECT p.id
                 FROM product_occasion_map pom
                 INNER JOIN products p ON p.id = pom.product_id
                 WHERE pom.occasion_id = :id
                 ORDER BY p.is_featured DESC, p.updated_at DESC, p.id DESC',
                ['id' => $occasionId]
            );

            if ($productIds === []) {
                continue;
            }

            $title = (string) ($occasion['name'] ?? 'Occasion Collection');
            $occasionOptions[] = $this->homepagePresetOption(
                $title,
                $title,
                'Occasion Collection',
                'View All',
                '/occasions?occasion=' . rawurlencode($slug),
                $productIds
            );
        }

        $categoryOptions = [];

        foreach ($this->productService->listCategories() as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            $slug = (string) ($category['slug'] ?? '');
            $productIds = $this->productIdsBySql(
                'SELECT p.id
                 FROM product_category_map pcm
                 INNER JOIN products p ON p.id = pcm.product_id
                 WHERE pcm.category_id = :id
                 ORDER BY p.is_featured DESC, p.updated_at DESC, p.id DESC',
                ['id' => $categoryId]
            );

            if ($productIds === []) {
                continue;
            }

            $title = (string) ($category['name'] ?? 'Category Collection');
            $categoryOptions[] = $this->homepagePresetOption(
                $title,
                $title,
                'Category Collection',
                'View All',
                '/search?category=' . rawurlencode($slug),
                $productIds
            );
        }

        return $this->filterEmptyGroups([
            ['label' => 'Store Collections', 'options' => $generalOptions],
            ['label' => 'Occasions', 'options' => $occasionOptions],
            ['label' => 'Categories', 'options' => $categoryOptions],
        ]);
    }

    /**
     * @param array<string, string> $params
     * @return array<int, int>
     */
    private function productIdsBySql(string $sql, array $params = []): array
    {
        $rows = $this->app->database()->fetchAll($sql, $params);
        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param array<int, string> $sqlStatements
     * @return array<int, string>
     */
    private function distinctValues(array $sqlStatements): array
    {
        $values = [];

        foreach ($sqlStatements as $sql) {
            foreach ($this->app->database()->fetchAll($sql) as $row) {
                $value = trim((string) ($row['value'] ?? ''));

                if ($value !== '') {
                    $values[$value] = true;
                }
            }
        }

        ksort($values);

        return array_keys($values);
    }

    /**
     * @param array<int, int> $productIds
     * @return array<string, mixed>
     */
    private function homepagePresetOption(
        string $label,
        string $title,
        string $subheading,
        string $ctaLabel,
        string $ctaUrl,
        array $productIds
    ): array {
        return $this->option($label, $ctaUrl, [
            'section-title' => $title,
            'section-subheading' => $subheading,
            'section-cta-label' => $ctaLabel,
            'section-cta-url' => $ctaUrl,
            'product-ids' => implode(',', $productIds),
        ]);
    }

    /**
     * @param array<string, string> $data
     * @return array<string, mixed>
     */
    private function option(string $label, string $value, array $data = []): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'data' => $data,
        ];
    }

    /**
     * @param array<int, array{label: string, options: array<int, array<string, mixed>>}> $groups
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    private function filterEmptyGroups(array $groups): array
    {
        return array_values(array_filter(
            $groups,
            static fn (array $group): bool => ($group['options'] ?? []) !== []
        ));
    }

    private function labelizeKey(string $value): string
    {
        $value = str_replace(['-', '_', '.'], ' ', trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return ucwords($value);
    }
}
