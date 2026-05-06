<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class ProductService
{
    private const IMAGE_MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProducts(): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, name, slug, base_price, is_featured
             FROM products
             ORDER BY created_at DESC, id DESC'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProductsExcludingId(?int $excludedProductId = null): array
    {
        if ($excludedProductId === null || $excludedProductId <= 0) {
            return $this->listProducts();
        }

        return $this->app->database()->fetchAll(
            'SELECT id, name, slug, base_price, is_featured
             FROM products
             WHERE id <> :id
             ORDER BY created_at DESC, id DESC',
            ['id' => $excludedProductId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAddons(bool $activeOnly = false): array
    {
        $sql = 'SELECT id, name, slug, description, price, is_active, sort_order, created_at, updated_at
                FROM addons';
        $params = [];

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, name ASC, id ASC';

        return $this->app->database()->fetchAll($sql, $params);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listStorefrontProducts(int $limit = 6, array $criteria = []): array
    {
        return $this->listStorefrontProductsByCriteria($criteria, max(1, $limit));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listFeaturedStorefrontProducts(int $limit = 6, array $criteria = []): array
    {
        $criteria['featured_only'] = true;
        $products = $this->listStorefrontProductsByCriteria($criteria, max(1, $limit));

        if ($products !== []) {
            return $products;
        }

        return $this->listStorefrontProducts($limit, $criteria);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOccasionCollections(array $criteria = []): array
    {
        $criteria = $this->normalizeStorefrontCriteria($criteria);
        $joins = [
            'LEFT JOIN product_occasion_map pom ON pom.occasion_id = o.id',
            'LEFT JOIN products p ON p.id = pom.product_id',
        ];
        $conditions = [];
        $params = [];

        if ($criteria['featured_only']) {
            $conditions[] = 'p.is_featured = 1';
        }

        if ($criteria['category'] !== '') {
            $joins[] = 'LEFT JOIN product_category_map pcm ON pcm.product_id = p.id';
            $joins[] = 'LEFT JOIN categories c ON c.id = pcm.category_id';
            $conditions[] = 'c.slug = :category_slug';
            $params['category_slug'] = $criteria['category'];
        }

        if ($criteria['occasion'] !== '') {
            $conditions[] = 'o.slug = :occasion_slug';
            $params['occasion_slug'] = $criteria['occasion'];
        }

        if ($criteria['query'] !== '') {
            $conditions[] = '(
                p.name LIKE :search_query_name
                OR p.slug LIKE :search_query_slug
                OR p.description LIKE :search_query_description
                OR EXISTS (
                    SELECT 1
                    FROM product_category_map pcm2
                    INNER JOIN categories c2 ON c2.id = pcm2.category_id
                    WHERE pcm2.product_id = p.id
                      AND (c2.name LIKE :search_query_category_name OR c2.slug LIKE :search_query_category_slug)
                )
                OR EXISTS (
                    SELECT 1
                    FROM product_occasion_map pom2
                    INNER JOIN occasions o2 ON o2.id = pom2.occasion_id
                    WHERE pom2.product_id = p.id
                      AND (o2.name LIKE :search_query_occasion_name OR o2.slug LIKE :search_query_occasion_slug)
                )
            )';
            $searchValue = '%' . $criteria['query'] . '%';
            $params['search_query_name'] = $searchValue;
            $params['search_query_slug'] = $searchValue;
            $params['search_query_description'] = $searchValue;
            $params['search_query_category_name'] = $searchValue;
            $params['search_query_category_slug'] = $searchValue;
            $params['search_query_occasion_name'] = $searchValue;
            $params['search_query_occasion_slug'] = $searchValue;
        }

        $rows = $this->app->database()->fetchAll(
            'SELECT o.id AS occasion_id,
                    o.name AS occasion_name,
                    o.slug AS occasion_slug,
                    p.id AS product_id,
                    p.name AS product_name,
                    p.slug AS product_slug,
                    p.base_price AS product_base_price,
                    (
                        SELECT pi.image_path
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image_path,
                    (
                        SELECT MIN(pv.price_modifier)
                        FROM product_variants pv
                        WHERE pv.product_id = p.id
                    ) AS min_price_modifier
             FROM occasions o
             ' . implode("\n             ", $joins) . '
             ' . $this->buildWhereClause($conditions) . '
             ORDER BY o.name ASC, ' . $this->sortOrderClause($criteria['sort'], 'p')
            ,
            $params
        );

        $collections = [];

        foreach ($rows as $row) {
            $occasionId = (int) ($row['occasion_id'] ?? 0);

            if (!isset($collections[$occasionId])) {
                $collections[$occasionId] = [
                    'id' => $occasionId,
                    'name' => (string) ($row['occasion_name'] ?? ''),
                    'slug' => (string) ($row['occasion_slug'] ?? ''),
                    'products' => [],
                ];
            }

            $productId = (int) ($row['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            $collections[$occasionId]['products'][] = $this->hydrateDisplayProduct([
                'id' => $productId,
                'name' => (string) ($row['product_name'] ?? ''),
                'slug' => (string) ($row['product_slug'] ?? ''),
                'base_price' => $row['product_base_price'] ?? 0,
                'image_path' => $row['image_path'] ?? null,
                'min_price_modifier' => $row['min_price_modifier'] ?? null,
            ]);
        }

        return array_values($collections);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function listHomepageOccasionCollections(array $criteria = [], int $productsPerOccasion = 4, ?int $occasionLimit = null): array
    {
        $productsPerOccasion = max(1, $productsPerOccasion);
        $collections = $this->listOccasionCollections($criteria);
        $visibleCollections = [];

        foreach ($collections as $collection) {
            $products = array_values(array_filter(
                is_array($collection['products'] ?? null) ? $collection['products'] : [],
                static fn (mixed $product): bool => is_array($product) && (int) ($product['id'] ?? 0) > 0
            ));

            if ($products === []) {
                continue;
            }

            $collection['products'] = array_slice($products, 0, $productsPerOccasion);
            $collection['product_count'] = count($products);
            $visibleCollections[] = $collection;

            if ($occasionLimit !== null && $occasionLimit > 0 && count($visibleCollections) >= $occasionLimit) {
                break;
            }
        }

        return $visibleCollections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchStorefrontProducts(array $criteria = []): array
    {
        $criteria['limit'] = null;

        return $this->listStorefrontProductsByCriteria($criteria, null);
    }

    /**
     * @return array<string, string>
     */
    public function storefrontCriteriaFromInput(array $input): array
    {
        $query = trim((string) ($input['q'] ?? ''));
        $sort = trim((string) ($input['sort'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $occasion = trim((string) ($input['occasion'] ?? ''));
        $featured = trim((string) ($input['featured'] ?? ''));

        return $this->normalizeStorefrontCriteria([
            'query' => $query,
            'sort' => $sort,
            'category' => $this->normalizeSlugFilter($category),
            'occasion' => $this->normalizeSlugFilter($occasion),
            'featured' => $featured,
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function storefrontSortOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Default / Featured'],
            ['value' => 'name_asc', 'label' => 'Name A-Z'],
            ['value' => 'price_asc', 'label' => 'Price Low To High'],
            ['value' => 'price_desc', 'label' => 'Price High To Low'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCategories(): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, name, slug
            FROM categories
            ORDER BY name ASC, id ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findCategoryById(int $categoryId): ?array
    {
        $category = $this->app->database()->query(
            'SELECT id, name, slug
             FROM categories
             WHERE id = :id
             LIMIT 1',
            ['id' => $categoryId]
        )->fetch();

        return is_array($category) ? $category : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOccasions(): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, name, slug
             FROM occasions
             ORDER BY name ASC, id ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOccasionById(int $occasionId): ?array
    {
        $occasion = $this->app->database()->query(
            'SELECT id, name, slug
             FROM occasions
             WHERE id = :id
             LIMIT 1',
            ['id' => $occasionId]
        )->fetch();

        return is_array($occasion) ? $occasion : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAddonById(int $addonId): ?array
    {
        $addon = $this->app->database()->query(
            'SELECT id, name, slug, description, price, is_active, sort_order, created_at, updated_at
             FROM addons
             WHERE id = :id
             LIMIT 1',
            ['id' => $addonId]
        )->fetch();

        return is_array($addon) ? $addon : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findProductById(int $productId): ?array
    {
        $product = $this->app->database()->query(
            'SELECT id, name, slug, description, base_price, is_featured
             FROM products
             WHERE id = :id
             LIMIT 1',
            ['id' => $productId]
        )->fetch();

        return is_array($product) ? $product : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findStorefrontProductBySlug(string $slug): ?array
    {
        $product = $this->app->database()->query(
            'SELECT p.id, p.name, p.slug, p.description, p.base_price, p.is_featured,
                    (
                        SELECT pi.image_path
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image_path,
                    (
                        SELECT MIN(pv.price_modifier)
                        FROM product_variants pv
                        WHERE pv.product_id = p.id
                    ) AS min_price_modifier
             FROM products p
             WHERE p.slug = :slug
             LIMIT 1',
            ['slug' => $slug]
        )->fetch();

        if (!is_array($product)) {
            return null;
        }

        $productId = (int) ($product['id'] ?? 0);
        $product['variants'] = $this->listVariantsByProductId($productId);
        $product['images'] = $this->listImagesByProductId($productId);
        $product['categories'] = $this->listCategoryNamesByProductId($productId);
        $product['occasions'] = $this->listOccasionNamesByProductId($productId);
        $product['addons'] = $this->listAddonsByProductId($productId, true);
        $product['related_products'] = $this->listRelatedProductsByProductId($productId);

        return $this->hydrateDisplayProduct($product);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVariantByProductIdAndVariantId(int $productId, int $variantId): ?array
    {
        $variant = $this->app->database()->query(
            'SELECT id, product_id, name, price_modifier, sort_order
             FROM product_variants
             WHERE product_id = :product_id AND id = :id
             LIMIT 1',
            [
                'product_id' => $productId,
                'id' => $variantId,
            ]
        )->fetch();

        return is_array($variant) ? $variant : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildCartItemFromSelection(
        string $productSlug,
        int $variantId,
        int $quantity,
        array $addonIds = []
    ): ?array
    {
        if ($productSlug === '' || $variantId <= 0 || $quantity <= 0) {
            return null;
        }

        $product = $this->findStorefrontProductBySlug($productSlug);

        if ($product === null) {
            return null;
        }

        $productId = (int) ($product['id'] ?? 0);
        $variant = $this->findVariantByProductIdAndVariantId($productId, $variantId);

        if ($variant === null) {
            return null;
        }

        $unitPrice = max(
            0,
            (float) ($product['base_price'] ?? 0) + (float) ($variant['price_modifier'] ?? 0)
        );
        $addons = $this->buildSelectedAddonsForCartItem($productId, $addonIds);
        $addonUnitPrice = 0.0;

        foreach ($addons as $addon) {
            $addonUnitPrice += (float) ($addon['unit_price'] ?? 0);
        }

        $cartKeyParts = [$productId, $variantId];

        if ($addons !== []) {
            $cartKeyParts[] = implode('-', array_map(
                static fn (array $addon): string => (string) ($addon['addon_id'] ?? 0),
                $addons
            ));
        }

        return [
            'key' => implode(':', $cartKeyParts),
            'product_id' => $productId,
            'product_slug' => (string) ($product['slug'] ?? ''),
            'product_name' => (string) ($product['name'] ?? ''),
            'variant_id' => (int) ($variant['id'] ?? 0),
            'variant_name' => (string) ($variant['name'] ?? ''),
            'base_unit_price' => $unitPrice,
            'addon_unit_price' => $addonUnitPrice,
            'unit_price' => $unitPrice + $addonUnitPrice,
            'quantity' => $quantity,
            'image_path' => (string) ($product['image_path'] ?? ''),
            'addons' => $addons,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $cart
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, item_count: int}
     */
    public function summarizeCart(array $cart): array
    {
        $items = [];
        $subtotal = 0.0;
        $itemCount = 0;

        foreach ($cart as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $baseUnitPrice = max(0, (float) ($item['base_unit_price'] ?? ($item['unit_price'] ?? 0)));
            $addons = is_array($item['addons'] ?? null) ? $item['addons'] : [];
            $addonUnitPrice = 0.0;

            foreach ($addons as $index => $addon) {
                if (!is_array($addon)) {
                    unset($addons[$index]);
                    continue;
                }

                $addonPrice = max(0, (float) ($addon['unit_price'] ?? 0));
                $addons[$index]['unit_price'] = $addonPrice;
                $addons[$index]['quantity'] = $quantity;
                $addons[$index]['line_total'] = $addonPrice * $quantity;
                $addonUnitPrice += $addonPrice;
            }

            $addons = array_values($addons);
            $unitPrice = $baseUnitPrice + $addonUnitPrice;
            $lineTotal = $unitPrice * $quantity;
            $item['quantity'] = $quantity;
            $item['base_unit_price'] = $baseUnitPrice;
            $item['addon_unit_price'] = $addonUnitPrice;
            $item['unit_price'] = $unitPrice;
            $item['addons'] = $addons;
            $item['addon_line_total'] = $addonUnitPrice * $quantity;
            $item['line_total'] = $lineTotal;
            $items[] = $item;
            $subtotal += $lineTotal;
            $itemCount += $quantity;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'item_count' => $itemCount,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listVariantsByProductId(int $productId): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, name, price_modifier, sort_order
             FROM product_variants
             WHERE product_id = :product_id
             ORDER BY sort_order ASC, id ASC',
            ['product_id' => $productId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listImagesByProductId(int $productId): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, image_path, sort_order
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY sort_order ASC, id ASC',
            ['product_id' => $productId]
        );
    }

    /**
     * @return array<int, int>
     */
    public function listCategoryIdsByProductId(int $productId): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT category_id
             FROM product_category_map
             WHERE product_id = :product_id
             ORDER BY category_id ASC',
            ['product_id' => $productId]
        );

        return array_map(
            static fn (array $row): int => (int) $row['category_id'],
            $rows
        );
    }

    /**
     * @return array<int, int>
     */
    public function listOccasionIdsByProductId(int $productId): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT occasion_id
             FROM product_occasion_map
             WHERE product_id = :product_id
             ORDER BY occasion_id ASC',
            ['product_id' => $productId]
        );

        return array_map(
            static fn (array $row): int => (int) $row['occasion_id'],
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCategoryNamesByProductId(int $productId): array
    {
        return $this->app->database()->fetchAll(
            'SELECT c.id, c.name, c.slug
             FROM categories c
             INNER JOIN product_category_map pcm ON pcm.category_id = c.id
             WHERE pcm.product_id = :product_id
             ORDER BY c.name ASC, c.id ASC',
            ['product_id' => $productId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOccasionNamesByProductId(int $productId): array
    {
        return $this->app->database()->fetchAll(
            'SELECT o.id, o.name, o.slug
             FROM occasions o
             INNER JOIN product_occasion_map pom ON pom.occasion_id = o.id
             WHERE pom.product_id = :product_id
             ORDER BY o.name ASC, o.id ASC',
            ['product_id' => $productId]
        );
    }

    /**
     * @return array<int, int>
     */
    public function listAddonIdsByProductId(int $productId): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT addon_id
             FROM product_addon_map
             WHERE product_id = :product_id
             ORDER BY sort_order ASC, addon_id ASC',
            ['product_id' => $productId]
        );

        return array_map(
            static fn (array $row): int => (int) $row['addon_id'],
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAddonsByProductId(int $productId, bool $activeOnly = false): array
    {
        $sql = 'SELECT a.id, a.name, a.slug, a.description, a.price, a.is_active, a.sort_order
                FROM addons a
                INNER JOIN product_addon_map pam ON pam.addon_id = a.id
                WHERE pam.product_id = :product_id';
        $params = ['product_id' => $productId];

        if ($activeOnly) {
            $sql .= ' AND a.is_active = 1';
        }

        $sql .= ' ORDER BY pam.sort_order ASC, a.sort_order ASC, a.name ASC, a.id ASC';

        return $this->app->database()->fetchAll($sql, $params);
    }

    /**
     * @return array<int, int>
     */
    public function listRelatedProductIdsByProductId(int $productId): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT related_product_id
             FROM product_related_map
             WHERE product_id = :product_id
             ORDER BY sort_order ASC, related_product_id ASC',
            ['product_id' => $productId]
        );

        return array_map(
            static fn (array $row): int => (int) $row['related_product_id'],
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRelatedProductsByProductId(int $productId, int $limit = 4): array
    {
        if ($productId <= 0) {
            return [];
        }

        $sql = 'SELECT p.id, p.name, p.slug, p.base_price, p.is_featured,
                       (
                           SELECT pi.image_path
                           FROM product_images pi
                           WHERE pi.product_id = p.id
                           ORDER BY pi.sort_order ASC, pi.id ASC
                           LIMIT 1
                       ) AS image_path,
                       (
                           SELECT MIN(pv.price_modifier)
                           FROM product_variants pv
                           WHERE pv.product_id = p.id
                       ) AS min_price_modifier
                FROM product_related_map prm
                INNER JOIN products p ON p.id = prm.related_product_id
                WHERE prm.product_id = :product_id
                ORDER BY prm.sort_order ASC, p.name ASC, p.id ASC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $this->hydrateDisplayProducts(
            $this->app->database()->fetchAll($sql, ['product_id' => $productId])
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $variants
     * @param array<int, int> $categoryIds
     * @param array<int, int> $occasionIds
     */
    public function createProductWithVariants(
        array $data,
        array $variants,
        array $categoryIds = [],
        array $occasionIds = [],
        array $addonIds = [],
        array $relatedProductIds = []
    ): int
    {
        $addonIds = $this->sanitizeAddonIds($addonIds);
        $relatedProductIds = $this->sanitizeRelatedProductIds(0, $relatedProductIds);
        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());

        $pdo->beginTransaction();

        try {
            $queryBuilder->insert('products', [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'base_price' => $data['base_price'],
                'is_featured' => $data['is_featured'],
            ]);

            $productId = (int) $pdo->lastInsertId();

            foreach ($variants as $variant) {
                $queryBuilder->insert('product_variants', [
                    'product_id' => $productId,
                    'name' => $variant['name'],
                    'price_modifier' => $variant['price_modifier'],
                    'sort_order' => $variant['sort_order'],
                ]);
            }

            $this->replaceCategoryMappings($queryBuilder, $productId, $categoryIds);
            $this->replaceOccasionMappings($queryBuilder, $productId, $occasionIds);
            $this->replaceAddonMappings($queryBuilder, $productId, $addonIds);
            $this->replaceRelatedProductMappings($queryBuilder, $productId, $this->sanitizeRelatedProductIds($productId, $relatedProductIds));

            $pdo->commit();

            return $productId;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $variants
     * @param array<int, int> $categoryIds
     * @param array<int, int> $occasionIds
     */
    public function updateProductWithVariants(
        int $productId,
        array $data,
        array $variants,
        array $categoryIds = [],
        array $occasionIds = [],
        array $addonIds = [],
        array $relatedProductIds = []
    ): void
    {
        $addonIds = $this->sanitizeAddonIds($addonIds);
        $relatedProductIds = $this->sanitizeRelatedProductIds($productId, $relatedProductIds);
        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());

        $pdo->beginTransaction();

        try {
            $queryBuilder->update('products', [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'base_price' => $data['base_price'],
                'is_featured' => $data['is_featured'],
            ], [
                'id' => $productId,
            ]);

            $queryBuilder->delete('product_variants', [
                'product_id' => $productId,
            ]);

            foreach ($variants as $variant) {
                $queryBuilder->insert('product_variants', [
                    'product_id' => $productId,
                    'name' => $variant['name'],
                    'price_modifier' => $variant['price_modifier'],
                    'sort_order' => $variant['sort_order'],
                ]);
            }

            $this->replaceCategoryMappings($queryBuilder, $productId, $categoryIds);
            $this->replaceOccasionMappings($queryBuilder, $productId, $occasionIds);
            $this->replaceAddonMappings($queryBuilder, $productId, $addonIds);
            $this->replaceRelatedProductMappings($queryBuilder, $productId, $relatedProductIds);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $files
     */
    public function addProductImages(int $productId, array $files): void
    {
        if ($files === []) {
            return;
        }

        $targetDirectory = $this->app->getBasePath('public/uploads/products/' . $productId);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new \RuntimeException('Upload directory is not writable.');
        }

        $currentSortOrder = $this->nextImageSortOrder($productId);
        $storedPaths = [];

        foreach ($files as $file) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file((string) ($file['tmp_name'] ?? ''));
            $extension = self::IMAGE_MIME_MAP[$mimeType] ?? null;

            if ($extension === null) {
                throw new \RuntimeException('Unsupported image type.');
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $extension;
            $absolutePath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
            $relativePath = '/uploads/products/' . $productId . '/' . $filename;

            if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
                foreach ($storedPaths as $storedPath) {
                    if (is_file($storedPath)) {
                        unlink($storedPath);
                    }
                }

                throw new \RuntimeException('Unable to move uploaded image.');
            }

            $storedPaths[] = $absolutePath;

            $this->app->database()->execute(
                'INSERT INTO product_images (product_id, image_path, sort_order)
                 VALUES (:product_id, :image_path, :sort_order)',
                [
                    'product_id' => $productId,
                    'image_path' => $relativePath,
                    'sort_order' => $currentSortOrder,
                ]
            );

            $currentSortOrder++;
        }
    }

    /**
     * @param array<int, int|string> $imageIds
     * @param array<int, int|string> $sortOrders
     */
    public function updateProductImageSortOrders(int $productId, array $imageIds, array $sortOrders): void
    {
        $rows = $this->listImagesByProductId($productId);

        if ($rows === []) {
            return;
        }

        $allowedIds = [];

        foreach ($rows as $row) {
            $allowedIds[(int) ($row['id'] ?? 0)] = true;
        }

        $normalized = [];
        $count = max(count($imageIds), count($sortOrders));

        for ($index = 0; $index < $count; $index++) {
            $imageId = (int) ($imageIds[$index] ?? 0);

            if ($imageId <= 0 || !isset($allowedIds[$imageId])) {
                continue;
            }

            $normalized[$imageId] = max(0, (int) ($sortOrders[$index] ?? 0));
        }

        if ($normalized === []) {
            return;
        }

        foreach ($normalized as $imageId => $sortOrder) {
            $this->app->database()->execute(
                'UPDATE product_images
                 SET sort_order = :sort_order
                 WHERE id = :id AND product_id = :product_id',
                [
                    'id' => $imageId,
                    'product_id' => $productId,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    public function deleteProductImage(int $productId, int $imageId): void
    {
        $image = $this->findProductImageById($imageId);

        if ($image === null || (int) ($image['product_id'] ?? 0) !== $productId) {
            throw new \RuntimeException('Product image not found.');
        }

        $this->app->database()->execute(
            'DELETE FROM product_images
             WHERE id = :id AND product_id = :product_id',
            [
                'id' => $imageId,
                'product_id' => $productId,
            ]
        );

        $this->deleteFileIfPresent((string) ($image['image_path'] ?? ''));
        $this->normalizeProductImageSortOrders($productId);
    }

    /**
     * @return array{total:int,order_items:int,customer_reminders:int}
     */
    public function productOrderReferenceBreakdown(int $productId): array
    {
        if ($productId <= 0) {
            return ['total' => 0, 'order_items' => 0, 'customer_reminders' => 0];
        }

        $orderItemsRow = $this->app->database()->query(
            'SELECT COUNT(*) AS total
             FROM order_items
             WHERE product_id = :product_id',
            ['product_id' => $productId]
        )->fetch();
        $orderItemCount = (int) ($orderItemsRow['total'] ?? 0);

        $reminderRow = $this->app->database()->query(
            'SELECT COUNT(*) AS total
             FROM customer_reminders
             WHERE product_id = :product_id',
            ['product_id' => $productId]
        )->fetch();
        $reminderCount = (int) ($reminderRow['total'] ?? 0);

        return [
            'total' => $orderItemCount + $reminderCount,
            'order_items' => $orderItemCount,
            'customer_reminders' => $reminderCount,
        ];
    }

    public function productHasOrderReferences(int $productId): bool
    {
        return $this->productOrderReferenceBreakdown($productId)['total'] > 0;
    }

    public function deleteProduct(int $productId): void
    {
        $product = $this->findProductById($productId);

        if ($product === null) {
            throw new \RuntimeException('Product not found.');
        }

        $referenceCounts = $this->productOrderReferenceBreakdown($productId);

        if ($referenceCounts['total'] > 0) {
            throw new \RuntimeException(sprintf(
                'Blocked by order history references (order_items=%d, customer_reminders=%d).',
                $referenceCounts['order_items'],
                $referenceCounts['customer_reminders']
            ));
        }

        $images = $this->listImagesByProductId($productId);
        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());

        $pdo->beginTransaction();

        try {
            $queryBuilder->delete('product_category_map', ['product_id' => $productId]);
            $queryBuilder->delete('product_occasion_map', ['product_id' => $productId]);
            $queryBuilder->delete('product_addon_map', ['product_id' => $productId]);
            $queryBuilder->delete('product_related_map', ['product_id' => $productId]);
            $queryBuilder->delete('product_related_map', ['related_product_id' => $productId]);
            $queryBuilder->delete('product_images', ['product_id' => $productId]);
            $queryBuilder->delete('product_variants', ['product_id' => $productId]);
            $queryBuilder->delete('customer_reminders', ['product_id' => $productId]);
            $queryBuilder->delete('products', [
                'id' => $productId,
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        foreach ($images as $image) {
            $this->deleteFileIfPresent((string) ($image['image_path'] ?? ''));
        }

        $productDirectory = $this->app->getBasePath('public/uploads/products/' . $productId);

        if (is_dir($productDirectory)) {
            @rmdir($productDirectory);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): bool
    {
        return (new QueryBuilder($this->app->database()))->insert('categories', [
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCategory(int $categoryId, array $data): void
    {
        (new QueryBuilder($this->app->database()))->update('categories', [
            'name' => $data['name'],
            'slug' => $data['slug'],
        ], [
            'id' => $categoryId,
        ]);
    }

    public function deleteCategory(int $categoryId): void
    {
        (new QueryBuilder($this->app->database()))->delete('categories', [
            'id' => $categoryId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOccasion(array $data): bool
    {
        return (new QueryBuilder($this->app->database()))->insert('occasions', [
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAddon(array $data): bool
    {
        return (new QueryBuilder($this->app->database()))->insert('addons', [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'price' => $data['price'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAddon(int $addonId, array $data): void
    {
        (new QueryBuilder($this->app->database()))->update('addons', [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'price' => $data['price'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ], [
            'id' => $addonId,
        ]);
    }

    public function deleteAddon(int $addonId): void
    {
        (new QueryBuilder($this->app->database()))->delete('addons', [
            'id' => $addonId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateOccasion(int $occasionId, array $data): void
    {
        (new QueryBuilder($this->app->database()))->update('occasions', [
            'name' => $data['name'],
            'slug' => $data['slug'],
        ], [
            'id' => $occasionId,
        ]);
    }

    public function deleteOccasion(int $occasionId): void
    {
        (new QueryBuilder($this->app->database()))->delete('occasions', [
            'id' => $occasionId,
        ]);
    }

    /**
     * @param array<int, int> $categoryIds
     */
    private function replaceCategoryMappings(QueryBuilder $queryBuilder, int $productId, array $categoryIds): void
    {
        $queryBuilder->delete('product_category_map', [
            'product_id' => $productId,
        ]);

        foreach ($categoryIds as $categoryId) {
            $queryBuilder->insert('product_category_map', [
                'product_id' => $productId,
                'category_id' => $categoryId,
            ]);
        }
    }

    /**
     * @param array<int, int> $occasionIds
     */
    private function replaceOccasionMappings(QueryBuilder $queryBuilder, int $productId, array $occasionIds): void
    {
        $queryBuilder->delete('product_occasion_map', [
            'product_id' => $productId,
        ]);

        foreach ($occasionIds as $occasionId) {
            $queryBuilder->insert('product_occasion_map', [
                'product_id' => $productId,
                'occasion_id' => $occasionId,
            ]);
        }
    }

    /**
     * @param array<int, int> $addonIds
     */
    private function replaceAddonMappings(QueryBuilder $queryBuilder, int $productId, array $addonIds): void
    {
        $queryBuilder->delete('product_addon_map', [
            'product_id' => $productId,
        ]);

        $sortOrder = 1;

        foreach ($addonIds as $addonId) {
            $queryBuilder->insert('product_addon_map', [
                'product_id' => $productId,
                'addon_id' => $addonId,
                'sort_order' => $sortOrder,
            ]);
            $sortOrder++;
        }
    }

    /**
     * @param array<int, int> $relatedProductIds
     */
    private function replaceRelatedProductMappings(QueryBuilder $queryBuilder, int $productId, array $relatedProductIds): void
    {
        $queryBuilder->delete('product_related_map', [
            'product_id' => $productId,
        ]);

        $sortOrder = 1;

        foreach ($relatedProductIds as $relatedProductId) {
            $queryBuilder->insert('product_related_map', [
                'product_id' => $productId,
                'related_product_id' => $relatedProductId,
                'sort_order' => $sortOrder,
            ]);
            $sortOrder++;
        }
    }

    /**
     * @param array<int, int> $relatedProductIds
     * @return array<int, int>
     */
    private function sanitizeRelatedProductIds(int $productId, array $relatedProductIds): array
    {
        $sanitized = [];

        foreach ($relatedProductIds as $relatedProductId) {
            $relatedProductId = (int) $relatedProductId;

            if ($relatedProductId <= 0 || $relatedProductId === $productId) {
                continue;
            }

            if ($this->findProductById($relatedProductId) === null) {
                continue;
            }

            $sanitized[$relatedProductId] = $relatedProductId;
        }

        return array_values($sanitized);
    }

    /**
     * @param array<int, int> $addonIds
     * @return array<int, int>
     */
    private function sanitizeAddonIds(array $addonIds): array
    {
        $sanitized = [];

        foreach ($addonIds as $addonId) {
            $addonId = (int) $addonId;

            if ($addonId <= 0) {
                continue;
            }

            if ($this->findAddonById($addonId) === null) {
                continue;
            }

            $sanitized[$addonId] = $addonId;
        }

        return array_values($sanitized);
    }

    /**
     * @param array<int, int> $addonIds
     * @return array<int, array<string, mixed>>
     */
    private function buildSelectedAddonsForCartItem(int $productId, array $addonIds): array
    {
        $allowedAddons = $this->listAddonsByProductId($productId, true);
        $allowedById = [];

        foreach ($allowedAddons as $addon) {
            $allowedById[(int) ($addon['id'] ?? 0)] = $addon;
        }

        $selected = [];

        foreach ($addonIds as $addonId) {
            $addonId = (int) $addonId;

            if ($addonId <= 0 || !isset($allowedById[$addonId])) {
                continue;
            }

            $addon = $allowedById[$addonId];
            $selected[$addonId] = [
                'addon_id' => $addonId,
                'addon_name' => (string) ($addon['name'] ?? ''),
                'unit_price' => max(0, (float) ($addon['price'] ?? 0)),
                'quantity' => 1,
                'line_total' => max(0, (float) ($addon['price'] ?? 0)),
            ];
        }

        return array_values($selected);
    }

    private function nextImageSortOrder(int $productId): int
    {
        $row = $this->app->database()->query(
            'SELECT MAX(sort_order) AS max_sort_order
             FROM product_images
             WHERE product_id = :product_id',
            ['product_id' => $productId]
        )->fetch();

        $currentMax = is_array($row) ? (int) ($row['max_sort_order'] ?? 0) : 0;

        return $currentMax + 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProductImageById(int $imageId): ?array
    {
        if ($imageId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT id, product_id, image_path, sort_order
             FROM product_images
             WHERE id = :id
             LIMIT 1',
            ['id' => $imageId]
        )->fetch();

        return is_array($row) ? $row : null;
    }

    private function normalizeProductImageSortOrders(int $productId): void
    {
        $images = $this->listImagesByProductId($productId);
        $sortOrder = 1;

        foreach ($images as $image) {
            $imageId = (int) ($image['id'] ?? 0);

            if ($imageId <= 0) {
                continue;
            }

            $this->app->database()->execute(
                'UPDATE product_images
                 SET sort_order = :sort_order
                 WHERE id = :id',
                [
                    'id' => $imageId,
                    'sort_order' => $sortOrder,
                ]
            );

            $sortOrder++;
        }
    }

    private function deleteFileIfPresent(string $publicPath): void
    {
        $publicPath = trim($publicPath);

        if ($publicPath === '' || strpos($publicPath, '/uploads/') !== 0) {
            return;
        }

        $absolutePath = $this->app->getBasePath('public' . $publicPath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function hydrateDisplayProducts(array $products): array
    {
        return array_map(fn (array $product): array => $this->hydrateDisplayProduct($product), $products);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    private function listStorefrontProductsByCriteria(array $criteria, ?int $limit): array
    {
        $criteria = $this->normalizeStorefrontCriteria($criteria);
        $joins = [];
        $conditions = [];
        $params = [];

        if ($criteria['category'] !== '') {
            $joins[] = 'INNER JOIN product_category_map pcm ON pcm.product_id = p.id';
            $joins[] = 'INNER JOIN categories c ON c.id = pcm.category_id';
            $conditions[] = 'c.slug = :category_slug';
            $params['category_slug'] = $criteria['category'];
        }

        if ($criteria['occasion'] !== '') {
            $joins[] = 'INNER JOIN product_occasion_map pom ON pom.product_id = p.id';
            $joins[] = 'INNER JOIN occasions o ON o.id = pom.occasion_id';
            $conditions[] = 'o.slug = :occasion_slug';
            $params['occasion_slug'] = $criteria['occasion'];
        }

        if ($criteria['featured_only']) {
            $conditions[] = 'p.is_featured = 1';
        }

        if ($criteria['query'] !== '') {
            $conditions[] = '(
                p.name LIKE :search_query_name
                OR p.slug LIKE :search_query_slug
                OR p.description LIKE :search_query_description
                OR EXISTS (
                    SELECT 1
                    FROM product_category_map pcm2
                    INNER JOIN categories c2 ON c2.id = pcm2.category_id
                    WHERE pcm2.product_id = p.id
                      AND (c2.name LIKE :search_query_category_name OR c2.slug LIKE :search_query_category_slug)
                )
                OR EXISTS (
                    SELECT 1
                    FROM product_occasion_map pom2
                    INNER JOIN occasions o2 ON o2.id = pom2.occasion_id
                    WHERE pom2.product_id = p.id
                      AND (o2.name LIKE :search_query_occasion_name OR o2.slug LIKE :search_query_occasion_slug)
                )
            )';
            $searchValue = '%' . $criteria['query'] . '%';
            $params['search_query_name'] = $searchValue;
            $params['search_query_slug'] = $searchValue;
            $params['search_query_description'] = $searchValue;
            $params['search_query_category_name'] = $searchValue;
            $params['search_query_category_slug'] = $searchValue;
            $params['search_query_occasion_name'] = $searchValue;
            $params['search_query_occasion_slug'] = $searchValue;
        }

        $sql = 'SELECT DISTINCT p.id, p.name, p.slug, p.base_price, p.is_featured,
                        (
                            SELECT pi.image_path
                            FROM product_images pi
                            WHERE pi.product_id = p.id
                            ORDER BY pi.sort_order ASC, pi.id ASC
                            LIMIT 1
                        ) AS image_path,
                        (
                            SELECT MIN(pv.price_modifier)
                            FROM product_variants pv
                            WHERE pv.product_id = p.id
                        ) AS min_price_modifier
                 FROM products p
                 ' . implode("\n                 ", $joins) . '
                 ' . $this->buildWhereClause($conditions) . '
                 ORDER BY ' . $this->sortOrderClause($criteria['sort'], 'p');

        if ($limit !== null) {
            $sql .= "\n                 LIMIT " . max(1, $limit);
        }

        return $this->hydrateDisplayProducts(
            $this->app->database()->fetchAll($sql, $params)
        );
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array{query: string, sort: string, category: string, occasion: string, featured_only: bool}
     */
    private function normalizeStorefrontCriteria(array $criteria): array
    {
        $sort = trim((string) ($criteria['sort'] ?? ''));

        if (!in_array($sort, ['', 'name_asc', 'price_asc', 'price_desc'], true)) {
            $sort = '';
        }

        return [
            'query' => trim((string) ($criteria['query'] ?? '')),
            'sort' => $sort,
            'category' => $this->normalizeSlugFilter((string) ($criteria['category'] ?? '')),
            'occasion' => $this->normalizeSlugFilter((string) ($criteria['occasion'] ?? '')),
            'featured_only' => (($criteria['featured'] ?? null) === '1') || (($criteria['featured_only'] ?? false) === true),
        ];
    }

    private function normalizeSlugFilter(string $value): string
    {
        $value = trim($value);

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1 ? $value : '';
    }

    /**
     * @param array<int, string> $conditions
     */
    private function buildWhereClause(array $conditions): string
    {
        if ($conditions === []) {
            return '';
        }

        return 'WHERE ' . implode("\n                   AND ", $conditions);
    }

    private function sortOrderClause(string $sort, string $productAlias): string
    {
        return match ($sort) {
            'name_asc' => $productAlias . '.name ASC, ' . $productAlias . '.id ASC',
            'price_asc' => 'COALESCE(' . $productAlias . '.base_price + (
                SELECT MIN(pv.price_modifier)
                FROM product_variants pv
                WHERE pv.product_id = ' . $productAlias . '.id
            ), ' . $productAlias . '.base_price) ASC, ' . $productAlias . '.name ASC, ' . $productAlias . '.id ASC',
            'price_desc' => 'COALESCE(' . $productAlias . '.base_price + (
                SELECT MIN(pv.price_modifier)
                FROM product_variants pv
                WHERE pv.product_id = ' . $productAlias . '.id
            ), ' . $productAlias . '.base_price) DESC, ' . $productAlias . '.name ASC, ' . $productAlias . '.id ASC',
            default => $productAlias . '.is_featured DESC, ' . $productAlias . '.updated_at DESC, ' . $productAlias . '.id DESC',
        };
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function hydrateDisplayProduct(array $product): array
    {
        $basePrice = (float) ($product['base_price'] ?? 0);
        $minModifier = isset($product['min_price_modifier']) ? (float) $product['min_price_modifier'] : 0.0;
        $displayPrice = max(0, $basePrice + $minModifier);

        $product['display_price'] = $displayPrice;
        $product['image_path'] = (string) ($product['image_path'] ?? '');

        return $product;
    }
}
