<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class NavigationService
{
    private const STOREFRONT_PRIMARY_KEY = 'storefront-primary';
    private const STOREFRONT_PRIMARY_PLACEMENT = 'storefront-primary';
    private const TARGET_SELF = '_self';
    private const TARGET_BLANK = '_blank';
    private const DISPLAY_STYLE_LIST = 'list';
    private const DISPLAY_STYLE_MEGA = 'mega';

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMenuByKey(string $menuKey, bool $enabledOnly = true, bool $includeItems = true): ?array
    {
        $menuKey = trim($menuKey);

        if ($menuKey === '') {
            return null;
        }

        $sql = 'SELECT *
                FROM navigation_menus
                WHERE menu_key = :menu_key';
        $params = ['menu_key' => $menuKey];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' LIMIT 1';

        $row = $this->app->database()->query($sql, $params)->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrateMenu($row, $includeItems, $enabledOnly);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPrimaryStorefrontMenu(bool $enabledOnly = true, bool $includeItems = true): array
    {
        $this->ensureStorefrontPrimaryMenu();

        $menu = $this->findMenuByKey(self::STOREFRONT_PRIMARY_KEY, $enabledOnly, $includeItems);

        return is_array($menu) ? $menu : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMenusByPlacement(string $placement, bool $enabledOnly = true, bool $includeItems = true): array
    {
        $placement = trim($placement);

        if ($placement === '') {
            return [];
        }

        $sql = 'SELECT *
                FROM navigation_menus
                WHERE placement = :placement';
        $params = ['placement' => $placement];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY id ASC';

        $rows = $this->app->database()->fetchAll($sql, $params);

        return array_map(
            fn (array $row): array => $this->hydrateMenu($row, $includeItems, $enabledOnly),
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllMenus(bool $includeItems = false): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT *
             FROM navigation_menus
             ORDER BY placement ASC, name ASC, id ASC'
        );

        return array_map(
            fn (array $row): array => $this->hydrateMenu($row, $includeItems, false),
            $rows
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMenuById(int $menuId, bool $includeItems = true): ?array
    {
        if ($menuId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM navigation_menus
             WHERE id = :id
             LIMIT 1',
            ['id' => $menuId]
        )->fetch();

        return is_array($row) ? $this->hydrateMenu($row, $includeItems, false) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMenuItems(int $navigationMenuId, bool $enabledOnly = true): array
    {
        if ($navigationMenuId <= 0) {
            return [];
        }

        $sql = 'SELECT *
                FROM navigation_menu_items
                WHERE navigation_menu_id = :navigation_menu_id';
        $params = ['navigation_menu_id' => $navigationMenuId];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $rows = $this->app->database()->fetchAll($sql, $params);
        $items = array_map(fn (array $row): array => $this->hydrateMenuItem($row), $rows);

        return $this->buildTree($items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listFlatMenuItems(int $navigationMenuId, bool $enabledOnly = false): array
    {
        if ($navigationMenuId <= 0) {
            return [];
        }

        $sql = 'SELECT *
                FROM navigation_menu_items
                WHERE navigation_menu_id = :navigation_menu_id';
        $params = ['navigation_menu_id' => $navigationMenuId];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY parent_id IS NOT NULL ASC, sort_order ASC, id ASC';

        $rows = $this->app->database()->fetchAll($sql, $params);

        return array_map(fn (array $row): array => $this->hydrateMenuItem($row), $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createMenuItem(int $navigationMenuId, array $data): void
    {
        $menu = $this->findMenuById($navigationMenuId, false);

        if ($menu === null) {
            throw new \RuntimeException('Navigation menu not found.');
        }

        $parentId = $this->validateParentId($navigationMenuId, (int) ($data['parent_id'] ?? 0));

        $this->app->database()->execute(
            'INSERT INTO navigation_menu_items (
                navigation_menu_id,
                parent_id,
                label,
                url,
                item_type,
                target,
                sort_order,
                is_enabled,
                meta_json
             ) VALUES (
                :navigation_menu_id,
                :parent_id,
                :label,
                :url,
                :item_type,
                :target,
                :sort_order,
                :is_enabled,
                :meta_json
             )',
            [
                'navigation_menu_id' => $navigationMenuId,
                'parent_id' => $parentId,
                'label' => trim((string) ($data['label'] ?? '')),
                'url' => $this->normalizeUrl((string) ($data['url'] ?? '')),
                'item_type' => $this->normalizeItemType((string) ($data['item_type'] ?? 'link')),
                'target' => $this->normalizeTarget($data['target'] ?? null),
                'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
                'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
                'meta_json' => $this->encodeItemMeta($data, $parentId),
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateMenuItem(int $itemId, array $data): void
    {
        $item = $this->findMenuItemById($itemId);

        if ($item === null) {
            throw new \RuntimeException('Navigation item not found.');
        }

        $parentId = $this->validateParentId((int) $item['navigation_menu_id'], (int) ($data['parent_id'] ?? 0), $itemId);

        $this->app->database()->execute(
            'UPDATE navigation_menu_items
             SET parent_id = :parent_id,
                 label = :label,
                 url = :url,
                 item_type = :item_type,
                 target = :target,
                 sort_order = :sort_order,
                 is_enabled = :is_enabled,
                 meta_json = :meta_json
             WHERE id = :id',
            [
                'id' => $itemId,
                'parent_id' => $parentId,
                'label' => trim((string) ($data['label'] ?? '')),
                'url' => $this->normalizeUrl((string) ($data['url'] ?? '')),
                'item_type' => $this->normalizeItemType((string) ($data['item_type'] ?? 'link')),
                'target' => $this->normalizeTarget($data['target'] ?? null),
                'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
                'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
                'meta_json' => $this->encodeItemMeta($data, $parentId),
            ]
        );
    }

    public function deleteMenuItem(int $itemId): void
    {
        if ($this->findMenuItemById($itemId) === null) {
            throw new \RuntimeException('Navigation item not found.');
        }

        $this->app->database()->execute(
            'DELETE FROM navigation_menu_items
             WHERE id = :id',
            ['id' => $itemId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMenuItemById(int $itemId): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM navigation_menu_items
             WHERE id = :id
             LIMIT 1',
            ['id' => $itemId]
        )->fetch();

        return is_array($row) ? $this->hydrateMenuItem($row) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topLevelParentOptions(int $navigationMenuId, ?int $excludeItemId = null): array
    {
        $items = $this->listFlatMenuItems($navigationMenuId, false);

        return array_values(array_filter($items, static function (array $item) use ($excludeItemId): bool {
            if (($item['parent_id'] ?? null) !== null) {
                return false;
            }

            return $excludeItemId === null || (int) ($item['id'] ?? 0) !== $excludeItemId;
        }));
    }

    public function ensureStorefrontPrimaryMenu(): void
    {
        $menu = $this->findMenuByKey(self::STOREFRONT_PRIMARY_KEY, false, false);

        if ($menu === null) {
            $this->app->database()->execute(
                'INSERT INTO navigation_menus (menu_key, name, placement, is_enabled)
                 VALUES (:menu_key, :name, :placement, 1)',
                [
                    'menu_key' => self::STOREFRONT_PRIMARY_KEY,
                    'name' => 'Storefront Primary',
                    'placement' => self::STOREFRONT_PRIMARY_PLACEMENT,
                ]
            );

            $menuId = (int) $this->app->database()->connection()->lastInsertId();
            $this->seedDefaultStorefrontPrimaryMenuItems($menuId);

            return;
        }

        $countRow = $this->app->database()->query(
            'SELECT COUNT(*) AS total
             FROM navigation_menu_items
             WHERE navigation_menu_id = :navigation_menu_id',
            ['navigation_menu_id' => (int) $menu['id']]
        )->fetch();

        if ((int) ($countRow['total'] ?? 0) === 0) {
            $this->seedDefaultStorefrontPrimaryMenuItems((int) $menu['id']);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateMenu(array $row, bool $includeItems, bool $enabledOnly): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_enabled'] = !empty($row['is_enabled']);

        if ($includeItems) {
            $row['items'] = $this->listMenuItems((int) $row['id'], $enabledOnly);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateMenuItem(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['navigation_menu_id'] = (int) ($row['navigation_menu_id'] ?? 0);
        $row['parent_id'] = isset($row['parent_id']) ? (int) $row['parent_id'] : null;
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['is_enabled'] = !empty($row['is_enabled']);
        $row['item_type'] = $this->normalizeItemType((string) ($row['item_type'] ?? 'link'));
        $row['target'] = $this->normalizeTarget($row['target'] ?? null);
        $row['meta'] = $this->decodeJson($row['meta_json'] ?? null);
        $row['display_style'] = $this->resolveDisplayStyle($row['meta'] ?? null);
        $row['group_title'] = $this->normalizeMetaText($row['meta']['group_title'] ?? null);
        $row['column_key'] = $this->normalizeMetaText($row['meta']['column_key'] ?? null);
        $row['children'] = [];
        $row['dropdown_groups'] = [];
        $row['is_mega_menu'] = false;

        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(array $items): array
    {
        $indexed = [];

        foreach ($items as $item) {
            $indexed[(int) $item['id']] = $item;
        }

        $tree = [];

        foreach ($indexed as $itemId => $item) {
            $parentId = $item['parent_id'];

            if ($parentId !== null && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$indexed[$itemId];
                continue;
            }

            $tree[] = &$indexed[$itemId];
        }

        $tree = array_values($tree);

        foreach ($tree as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $item['dropdown_groups'] = $this->buildDropdownGroups($item['children'] ?? []);
            $item['is_mega_menu'] = $item['display_style'] === self::DISPLAY_STYLE_MEGA
                && $item['dropdown_groups'] !== [];
        }
        unset($item);

        return $tree;
    }

    /**
     * @param array<int, array<string, mixed>> $seedItems
     */
    private function insertSeedItems(int $menuId, array $seedItems): void
    {
        $parentMap = [];

        foreach ($seedItems as $seed) {
            $parentId = null;

            if (isset($seed['parent_key'])) {
                $parentId = $parentMap[(string) $seed['parent_key']] ?? null;
            }

            $this->app->database()->execute(
                'INSERT INTO navigation_menu_items (
                    navigation_menu_id,
                    parent_id,
                    label,
                    url,
                    item_type,
                    target,
                    sort_order,
                    is_enabled,
                    meta_json
                 ) VALUES (
                    :navigation_menu_id,
                    :parent_id,
                    :label,
                    :url,
                    :item_type,
                    :target,
                    :sort_order,
                    :is_enabled,
                    :meta_json
                 )',
                [
                    'navigation_menu_id' => $menuId,
                    'parent_id' => $parentId,
                    'label' => (string) $seed['label'],
                    'url' => (string) $seed['url'],
                    'item_type' => 'link',
                    'target' => null,
                    'sort_order' => (int) $seed['sort_order'],
                    'is_enabled' => 1,
                    'meta_json' => null,
                ]
            );

            if (isset($seed['item_key'])) {
                $parentMap[(string) $seed['item_key']] = (int) $this->app->database()->connection()->lastInsertId();
            }
        }
    }

    private function seedDefaultStorefrontPrimaryMenuItems(int $menuId): void
    {
        $this->insertSeedItems($menuId, [
            ['item_key' => 'birthday', 'label' => 'Birthday', 'url' => '/best-sellers', 'sort_order' => 10],
            ['item_key' => 'sympathy', 'label' => 'Sympathy', 'url' => '/best-sellers', 'sort_order' => 20],
            ['item_key' => 'occasions', 'label' => 'Occasions', 'url' => '/occasions', 'sort_order' => 30],
            ['item_key' => 'flowers', 'label' => 'Flowers', 'url' => '/', 'sort_order' => 40],
            ['item_key' => 'gifts-food', 'label' => 'Gifts + Food', 'url' => '/contact', 'sort_order' => 50],
            ['item_key' => 'same-day', 'label' => 'Same Day', 'url' => '/checkout', 'sort_order' => 60],
            ['parent_key' => 'birthday', 'label' => 'Best Sellers', 'url' => '/best-sellers', 'sort_order' => 10],
            ['parent_key' => 'birthday', 'label' => 'Shop By Occasion', 'url' => '/occasions', 'sort_order' => 20],
            ['parent_key' => 'sympathy', 'label' => 'Occasion Collections', 'url' => '/occasions', 'sort_order' => 10],
            ['parent_key' => 'sympathy', 'label' => 'Speak With The Shop', 'url' => '/contact', 'sort_order' => 20],
            ['parent_key' => 'occasions', 'label' => 'All Occasions', 'url' => '/occasions', 'sort_order' => 10],
            ['parent_key' => 'occasions', 'label' => 'Best Selling Gifts', 'url' => '/best-sellers', 'sort_order' => 20],
            ['parent_key' => 'flowers', 'label' => 'Home Catalog', 'url' => '/', 'sort_order' => 10],
            ['parent_key' => 'flowers', 'label' => 'Best Sellers', 'url' => '/best-sellers', 'sort_order' => 20],
            ['parent_key' => 'gifts-food', 'label' => 'Gift-Ready Bouquets', 'url' => '/best-sellers', 'sort_order' => 10],
            ['parent_key' => 'gifts-food', 'label' => 'Card Message Checkout', 'url' => '/checkout', 'sort_order' => 20],
            ['parent_key' => 'same-day', 'label' => 'Checkout Rules', 'url' => '/checkout', 'sort_order' => 10],
            ['parent_key' => 'same-day', 'label' => 'Contact The Shop', 'url' => '/contact', 'sort_order' => 20],
        ]);
    }

    private function validateParentId(int $navigationMenuId, int $parentId, ?int $currentItemId = null): ?int
    {
        if ($parentId <= 0) {
            return null;
        }

        if ($currentItemId !== null && $currentItemId === $parentId) {
            throw new \RuntimeException('A navigation item cannot be its own parent.');
        }

        $parent = $this->findMenuItemById($parentId);

        if ($parent === null || (int) ($parent['navigation_menu_id'] ?? 0) !== $navigationMenuId) {
            throw new \RuntimeException('Choose a valid parent item.');
        }

        if (($parent['parent_id'] ?? null) !== null) {
            throw new \RuntimeException('Only one child level is allowed in storefront navigation.');
        }

        return $parentId;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            throw new \RuntimeException('URL is required.');
        }

        if ($url[0] === '/') {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        throw new \RuntimeException('Enter a valid relative or absolute URL.');
    }

    private function normalizeItemType(string $itemType): string
    {
        $itemType = trim(strtolower($itemType));

        return $itemType !== '' ? $itemType : 'link';
    }

    private function normalizeTarget(mixed $target): ?string
    {
        $target = is_string($target) ? trim($target) : '';

        if ($target === '' || $target === self::TARGET_SELF) {
            return null;
        }

        if ($target === self::TARGET_BLANK) {
            return self::TARGET_BLANK;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encodeItemMeta(array $data, ?int $parentId): ?string
    {
        $meta = [];

        if ($parentId === null) {
            $displayStyle = $this->normalizeDisplayStyle((string) ($data['display_style'] ?? self::DISPLAY_STYLE_LIST));

            if ($displayStyle !== self::DISPLAY_STYLE_LIST) {
                $meta['display_style'] = $displayStyle;
            }
        } else {
            $groupTitle = $this->normalizeMetaText($data['group_title'] ?? null);
            $columnKey = $this->normalizeMetaText($data['column_key'] ?? null);

            if ($groupTitle !== null) {
                $meta['group_title'] = $groupTitle;
            }

            if ($columnKey !== null) {
                $meta['column_key'] = $columnKey;
            }
        }

        if ($meta === []) {
            return null;
        }

        $json = json_encode($meta, JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : null;
    }

    private function normalizeDisplayStyle(string $displayStyle): string
    {
        $displayStyle = trim(strtolower($displayStyle));

        if ($displayStyle === self::DISPLAY_STYLE_MEGA) {
            return self::DISPLAY_STYLE_MEGA;
        }

        return self::DISPLAY_STYLE_LIST;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    private function resolveDisplayStyle(?array $meta): string
    {
        return $this->normalizeDisplayStyle((string) ($meta['display_style'] ?? self::DISPLAY_STYLE_LIST));
    }

    private function normalizeMetaText(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<int, array<string, mixed>>
     */
    private function buildDropdownGroups(array $children): array
    {
        if ($children === []) {
            return [];
        }

        $groups = [];

        foreach ($children as $child) {
            $columnKey = $this->normalizeMetaText($child['column_key'] ?? null) ?? 'default';
            $groupTitle = $this->normalizeMetaText($child['group_title'] ?? null);
            $groupKey = $columnKey . '::' . ($groupTitle ?? '');

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'column_key' => $columnKey,
                    'title' => $groupTitle,
                    'items' => [],
                ];
            }

            $groups[$groupKey]['items'][] = $child;
        }

        return array_values($groups);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
