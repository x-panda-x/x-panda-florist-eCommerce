<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class HomepageSectionService
{
    private Application $app;
    private bool $schemaReady = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listStorefrontSections(): array
    {
        $this->ensureFoundation();

        $sections = $this->app->database()->fetchAll(
            'SELECT id, section_key, title, subheading, cta_label, cta_url, is_active, sort_order
             FROM homepage_product_sections
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );

        $visibleSections = [];

        foreach ($sections as $section) {
            $products = $this->listProductsForSection((int) ($section['id'] ?? 0));

            if ($products === []) {
                continue;
            }

            $section['id'] = (int) ($section['id'] ?? 0);
            $section['is_active'] = !empty($section['is_active']);
            $section['sort_order'] = (int) ($section['sort_order'] ?? 0);
            $section['products'] = $products;
            $visibleSections[] = $section;
        }

        return $visibleSections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAdminSections(): array
    {
        $this->ensureFoundation();

        $sections = $this->app->database()->fetchAll(
            'SELECT id, section_key, title, subheading, cta_label, cta_url, is_active, sort_order
             FROM homepage_product_sections
             ORDER BY sort_order ASC, id ASC'
        );

        foreach ($sections as $index => $section) {
            $sectionId = (int) ($section['id'] ?? 0);
            $sections[$index]['id'] = $sectionId;
            $sections[$index]['is_active'] = !empty($section['is_active']);
            $sections[$index]['sort_order'] = (int) ($section['sort_order'] ?? 0);
            $sections[$index]['products'] = $this->listProductsForSection($sectionId);
        }

        return $sections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProductOptions(): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT p.id, p.name, p.slug, p.base_price, p.is_featured,
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
             ORDER BY p.name ASC, p.id ASC'
        );

        return array_map(fn (array $row): array => $this->hydrateDisplayProduct($row), $rows);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function saveFromAdminPayload(array $payload): void
    {
        $this->ensureFoundation();
        $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
        $newSection = is_array($payload['new_section'] ?? null) ? $payload['new_section'] : [];
        $allowedProductIds = $this->allowedProductIds();
        $normalizedSections = [];

        foreach ($sections as $sectionInput) {
            if (!is_array($sectionInput)) {
                continue;
            }

            $normalizedSections[] = $this->normalizeSectionInput($sectionInput, $allowedProductIds, false);
        }

        if ($this->newSectionHasInput($newSection)) {
            $normalizedSections[] = $this->normalizeSectionInput($newSection, $allowedProductIds, true);
        }

        $normalizedSections = $this->normalizeSortOrders($normalizedSections);

        $pdo = $this->app->database()->connection();
        $pdo->beginTransaction();

        try {
            foreach ($normalizedSections as $section) {
                $sectionId = (int) ($section['id'] ?? 0);

                if ($sectionId > 0) {
                    $existing = $this->findSectionById($sectionId);

                    if ($existing === null) {
                        continue;
                    }

                    $this->app->database()->execute(
                        'UPDATE homepage_product_sections
                         SET title = :title,
                             subheading = :subheading,
                             cta_label = :cta_label,
                             cta_url = :cta_url,
                             is_active = :is_active,
                             sort_order = :sort_order
                         WHERE id = :id',
                        [
                            'id' => $sectionId,
                            'title' => $section['title'],
                            'subheading' => $section['subheading'],
                            'cta_label' => $section['cta_label'],
                            'cta_url' => $section['cta_url'],
                            'is_active' => $section['is_active'],
                            'sort_order' => $section['sort_order'],
                        ]
                    );
                } else {
                    $sectionKey = $this->uniqueSectionKey((string) $section['title']);
                    $this->app->database()->execute(
                        'INSERT INTO homepage_product_sections (
                            section_key,
                            title,
                            subheading,
                            cta_label,
                            cta_url,
                            is_active,
                            sort_order
                         ) VALUES (
                            :section_key,
                            :title,
                            :subheading,
                            :cta_label,
                            :cta_url,
                            :is_active,
                            :sort_order
                         )',
                        [
                            'section_key' => $sectionKey,
                            'title' => $section['title'],
                            'subheading' => $section['subheading'],
                            'cta_label' => $section['cta_label'],
                            'cta_url' => $section['cta_url'],
                            'is_active' => $section['is_active'],
                            'sort_order' => $section['sort_order'],
                        ]
                    );
                    $sectionId = (int) $pdo->lastInsertId();
                }

                $this->replaceSectionProducts($sectionId, $section['products']);
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<int, array{id: int, title: string, subheading: string, cta_label: string, cta_url: string, is_active: int, sort_order: int, products: array<int, array{product_id: int, sort_order: int}>}> $sections
     * @return array<int, array{id: int, title: string, subheading: string, cta_label: string, cta_url: string, is_active: int, sort_order: int, products: array<int, array{product_id: int, sort_order: int}>}>
     */
    private function normalizeSortOrders(array $sections): array
    {
        usort($sections, static fn (array $left, array $right): int => ($left['sort_order'] <=> $right['sort_order']) ?: ($left['id'] <=> $right['id']));
        $sectionOrder = 10;

        foreach ($sections as $index => $section) {
            $sections[$index]['sort_order'] = $sectionOrder;
            $sectionOrder += 10;

            $products = $section['products'];
            usort($products, static fn (array $left, array $right): int => ($left['sort_order'] <=> $right['sort_order']) ?: ($left['product_id'] <=> $right['product_id']));

            $productOrder = 10;
            foreach ($products as $productIndex => $product) {
                $products[$productIndex]['sort_order'] = $productOrder;
                $productOrder += 10;
            }

            $sections[$index]['products'] = $products;
        }

        return $sections;
    }

    public function ensureFoundation(): void
    {
        $this->ensureSchema();
        $this->seedFromOccasionsIfEmpty();
    }

    private function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $pdo = $this->app->database()->connection();
        $sectionsTable = $pdo->query("SHOW TABLES LIKE 'homepage_product_sections'")->fetch();
        $productsTable = $pdo->query("SHOW TABLES LIKE 'homepage_product_section_products'")->fetch();

        if (is_array($sectionsTable) && is_array($productsTable)) {
            $this->schemaReady = true;
            return;
        }

        $migration = require $this->app->getBasePath('database/migrations/038_create_homepage_product_sections.php');

        if (!is_object($migration) || !method_exists($migration, 'up')) {
            throw new \RuntimeException('Homepage product section migration is unavailable.');
        }

        $migration->up($pdo);
        $this->schemaReady = true;
    }

    private function seedFromOccasionsIfEmpty(): void
    {
        $row = $this->app->database()->query(
            'SELECT COUNT(*) AS total
             FROM homepage_product_sections'
        )->fetch();

        if ((int) ($row['total'] ?? 0) > 0) {
            return;
        }

        $occasions = $this->app->database()->fetchAll(
            'SELECT o.id, o.name, o.slug
             FROM occasions o
             WHERE EXISTS (
                SELECT 1
                FROM product_occasion_map pom
                WHERE pom.occasion_id = o.id
             )
             ORDER BY o.name ASC, o.id ASC'
        );

        $sortOrder = 10;

        foreach ($occasions as $occasion) {
            $sectionKey = 'occasion-' . $this->slugify((string) ($occasion['slug'] ?? $occasion['name'] ?? 'section'));
            $this->app->database()->execute(
                'INSERT INTO homepage_product_sections (
                    section_key,
                    title,
                    subheading,
                    cta_label,
                    cta_url,
                    is_active,
                    sort_order
                 ) VALUES (
                    :section_key,
                    :title,
                    :subheading,
                    :cta_label,
                    :cta_url,
                    1,
                    :sort_order
                 )',
                [
                    'section_key' => $this->uniqueSectionKey($sectionKey),
                    'title' => (string) ($occasion['name'] ?? 'Occasion Collection'),
                    'subheading' => 'Occasion Collection',
                    'cta_label' => 'View All',
                    'cta_url' => '/occasions?occasion=' . rawurlencode((string) ($occasion['slug'] ?? '')),
                    'sort_order' => $sortOrder,
                ]
            );

            $sectionId = (int) $this->app->database()->connection()->lastInsertId();
            $productRows = $this->app->database()->fetchAll(
                'SELECT p.id
                 FROM product_occasion_map pom
                 INNER JOIN products p ON p.id = pom.product_id
                 WHERE pom.occasion_id = :occasion_id
                 ORDER BY p.is_featured DESC, p.updated_at DESC, p.id DESC',
                ['occasion_id' => (int) ($occasion['id'] ?? 0)]
            );
            $productSortOrder = 10;

            foreach ($productRows as $productRow) {
                $this->app->database()->execute(
                    'INSERT IGNORE INTO homepage_product_section_products (section_id, product_id, sort_order)
                     VALUES (:section_id, :product_id, :sort_order)',
                    [
                        'section_id' => $sectionId,
                        'product_id' => (int) ($productRow['id'] ?? 0),
                        'sort_order' => $productSortOrder,
                    ]
                );
                $productSortOrder += 10;
            }

            $sortOrder += 10;
        }

        if ($occasions === []) {
            $this->seedFallbackProductSection();
        }
    }

    private function seedFallbackProductSection(): void
    {
        $products = $this->app->database()->fetchAll(
            'SELECT p.id
             FROM products p
             ORDER BY p.is_featured DESC, p.updated_at DESC, p.id DESC'
        );

        if ($products === []) {
            return;
        }

        $this->app->database()->execute(
            'INSERT INTO homepage_product_sections (
                section_key,
                title,
                subheading,
                cta_label,
                cta_url,
                is_active,
                sort_order
             ) VALUES (
                :section_key,
                :title,
                :subheading,
                :cta_label,
                :cta_url,
                1,
                10
             )',
            [
                'section_key' => 'featured-flowers',
                'title' => 'Featured Flowers',
                'subheading' => 'Collection',
                'cta_label' => 'View All',
                'cta_url' => '/best-sellers',
            ]
        );

        $sectionId = (int) $this->app->database()->connection()->lastInsertId();
        $sortOrder = 10;

        foreach ($products as $product) {
            $this->app->database()->execute(
                'INSERT IGNORE INTO homepage_product_section_products (section_id, product_id, sort_order)
                 VALUES (:section_id, :product_id, :sort_order)',
                [
                    'section_id' => $sectionId,
                    'product_id' => (int) ($product['id'] ?? 0),
                    'sort_order' => $sortOrder,
                ]
            );
            $sortOrder += 10;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findSectionById(int $sectionId): ?array
    {
        if ($sectionId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT id, section_key, title
             FROM homepage_product_sections
             WHERE id = :id
             LIMIT 1',
            ['id' => $sectionId]
        )->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listProductsForSection(int $sectionId): array
    {
        if ($sectionId <= 0) {
            return [];
        }

        $rows = $this->app->database()->fetchAll(
            'SELECT p.id, p.name, p.slug, p.base_price, p.is_featured,
                    hpsp.sort_order AS section_sort_order,
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
             FROM homepage_product_section_products hpsp
             INNER JOIN products p ON p.id = hpsp.product_id
             WHERE hpsp.section_id = :section_id
             ORDER BY hpsp.sort_order ASC, p.name ASC, p.id ASC',
            ['section_id' => $sectionId]
        );

        return array_map(fn (array $row): array => $this->hydrateDisplayProduct($row), $rows);
    }

    /**
     * @return array<int, true>
     */
    private function allowedProductIds(): array
    {
        $allowed = [];

        foreach ($this->listProductOptions() as $product) {
            $productId = (int) ($product['id'] ?? 0);

            if ($productId > 0) {
                $allowed[$productId] = true;
            }
        }

        return $allowed;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, true> $allowedProductIds
     * @return array{id: int, title: string, subheading: string, cta_label: string, cta_url: string, is_active: int, sort_order: int, products: array<int, array{product_id: int, sort_order: int}>}
     */
    private function normalizeSectionInput(array $input, array $allowedProductIds, bool $isNew): array
    {
        $sectionId = max(0, (int) ($input['id'] ?? 0));
        $title = trim((string) ($input['title'] ?? ''));
        $subheading = trim((string) ($input['subheading'] ?? ''));
        $ctaLabel = trim((string) ($input['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($input['cta_url'] ?? ''));
        $sortOrderValue = trim((string) ($input['sort_order'] ?? '0'));

        if ($sortOrderValue === '') {
            $sortOrderValue = '0';
        }

        if ($title === '') {
            throw new \InvalidArgumentException($isNew ? 'Enter a title for the new homepage section.' : 'Each homepage section needs a title.');
        }

        if (!ctype_digit($sortOrderValue)) {
            throw new \InvalidArgumentException('Section order must be a non-negative whole number.');
        }

        if ($ctaUrl !== '' && strpos($ctaUrl, '/') !== 0 && filter_var($ctaUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Section links must be valid relative or absolute URLs.');
        }

        $productIds = is_array($input['product_ids'] ?? null) ? $input['product_ids'] : [];
        $sortOrders = is_array($input['product_sort_orders'] ?? null) ? $input['product_sort_orders'] : [];
        $products = [];
        $fallbackSortOrder = 10;

        foreach ($productIds as $productIdValue) {
            $productId = (int) $productIdValue;

            if ($productId <= 0 || !isset($allowedProductIds[$productId]) || isset($products[$productId])) {
                continue;
            }

            $productSortOrderValue = trim((string) ($sortOrders[$productId] ?? $fallbackSortOrder));

            if ($productSortOrderValue !== '' && !ctype_digit($productSortOrderValue)) {
                throw new \InvalidArgumentException('Product order must be a non-negative whole number.');
            }

            $products[$productId] = [
                'product_id' => $productId,
                'sort_order' => $productSortOrderValue !== '' ? (int) $productSortOrderValue : $fallbackSortOrder,
            ];
            $fallbackSortOrder += 10;
        }

        return [
            'id' => $sectionId,
            'title' => $title,
            'subheading' => $subheading,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'sort_order' => (int) $sortOrderValue,
            'products' => array_values($products),
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function newSectionHasInput(array $input): bool
    {
        foreach (['title', 'cta_label', 'cta_url'] as $key) {
            if (trim((string) ($input[$key] ?? '')) !== '') {
                return true;
            }
        }

        return !empty($input['product_ids']);
    }

    /**
     * @param array<int, array{product_id: int, sort_order: int}> $products
     */
    private function replaceSectionProducts(int $sectionId, array $products): void
    {
        if ($sectionId <= 0) {
            return;
        }

        $this->app->database()->execute(
            'DELETE FROM homepage_product_section_products
             WHERE section_id = :section_id',
            ['section_id' => $sectionId]
        );

        foreach ($products as $product) {
            $this->app->database()->execute(
                'INSERT INTO homepage_product_section_products (section_id, product_id, sort_order)
                 VALUES (:section_id, :product_id, :sort_order)',
                [
                    'section_id' => $sectionId,
                    'product_id' => (int) $product['product_id'],
                    'sort_order' => (int) $product['sort_order'],
                ]
            );
        }
    }

    private function uniqueSectionKey(string $source): string
    {
        $base = $this->slugify($source);

        if ($base === '') {
            $base = 'homepage-section';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->sectionKeyExists($candidate)) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function sectionKeyExists(string $sectionKey): bool
    {
        $row = $this->app->database()->query(
            'SELECT id
             FROM homepage_product_sections
             WHERE section_key = :section_key
             LIMIT 1',
            ['section_key' => $sectionKey]
        )->fetch();

        return is_array($row);
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'homepage-section';
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function hydrateDisplayProduct(array $product): array
    {
        $basePrice = (float) ($product['base_price'] ?? 0);
        $minModifier = isset($product['min_price_modifier']) ? (float) $product['min_price_modifier'] : 0.0;

        $product['id'] = (int) ($product['id'] ?? 0);
        $product['display_price'] = max(0, $basePrice + $minModifier);
        $product['image_path'] = (string) ($product['image_path'] ?? '');
        $product['section_sort_order'] = isset($product['section_sort_order']) ? (int) $product['section_sort_order'] : 0;

        return $product;
    }
}
