<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\AdminSelectionService;
use App\Services\NavigationService;

final class NavigationController extends BaseAdminController
{
    private AdminSelectionService $adminSelectionService;
    private NavigationService $navigationService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->adminSelectionService = new AdminSelectionService($app);
        $this->navigationService = new NavigationService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $this->navigationService->ensureStorefrontPrimaryMenu();
        $menu = $this->navigationService->getPrimaryStorefrontMenu(false, true);

        return $this->renderAdmin('admin-navigation', [
            'pageTitle' => 'Navigation',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'menu' => $menu,
            'items' => $menu !== [] ? $this->navigationService->listFlatMenuItems((int) ($menu['id'] ?? 0), false) : [],
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();
        $this->navigationService->ensureStorefrontPrimaryMenu();
        $menu = $this->navigationService->getPrimaryStorefrontMenu(false, false);

        return $this->renderForm([
            'pageTitle' => 'Add Navigation Item',
            'formAction' => '/admin/navigation',
            'formMode' => 'create',
            'menu' => $menu,
            'item' => $this->emptyItem(),
            'parentOptions' => $menu !== [] ? $this->navigationService->topLevelParentOptions((int) ($menu['id'] ?? 0)) : [],
            'groupTitleOptions' => $menu !== [] ? $this->buildGroupTitleOptions((int) ($menu['id'] ?? 0)) : [],
            'columnKeyOptions' => $menu !== [] ? $this->buildColumnKeyOptions((int) ($menu['id'] ?? 0)) : [],
            'linkOptions' => $this->adminSelectionService->linkOptions(),
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();
        $itemId = (int) ($_GET['id'] ?? 0);
        $item = $this->navigationService->findMenuItemById($itemId);

        if ($item === null) {
            $this->flash('error', 'Navigation item not found.');
            $this->redirect('/admin/navigation');
        }

        $menu = $this->navigationService->findMenuById((int) ($item['navigation_menu_id'] ?? 0), false);

        return $this->renderForm([
            'pageTitle' => 'Edit Navigation Item',
            'formAction' => '/admin/navigation/update',
            'formMode' => 'edit',
            'menu' => $menu,
            'item' => $item,
            'itemId' => $itemId,
            'parentOptions' => $this->navigationService->topLevelParentOptions((int) ($item['navigation_menu_id'] ?? 0), $itemId),
            'groupTitleOptions' => $this->buildGroupTitleOptions((int) ($item['navigation_menu_id'] ?? 0)),
            'columnKeyOptions' => $this->buildColumnKeyOptions((int) ($item['navigation_menu_id'] ?? 0)),
            'linkOptions' => $this->adminSelectionService->linkOptions(),
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/navigation/create');
        }

        $menuId = (int) ($_POST['navigation_menu_id'] ?? 0);
        $item = $this->normalizeItem($_POST);
        $validationError = $this->validateItem($item);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/navigation/create');
        }

        try {
            $this->navigationService->createMenuItem($menuId, $item);
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/admin/navigation/create');
        }

        $this->flash('success', 'Navigation item created.');
        $this->redirect('/admin/navigation');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/navigation');
        }

        $itemId = (int) ($_POST['id'] ?? 0);
        $item = $this->normalizeItem($_POST);
        $validationError = $this->validateItem($item);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/navigation/edit?id=' . $itemId);
        }

        try {
            $this->navigationService->updateMenuItem($itemId, $item);
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/admin/navigation/edit?id=' . $itemId);
        }

        $this->flash('success', 'Navigation item updated.');
        $this->redirect('/admin/navigation');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/navigation');
        }

        try {
            $this->navigationService->deleteMenuItem((int) ($_POST['id'] ?? 0));
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('/admin/navigation');
        }

        $this->flash('success', 'Navigation item deleted.');
        $this->redirect('/admin/navigation');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeItem(array $input): array
    {
        return [
            'label' => trim((string) ($input['label'] ?? '')),
            'url' => trim((string) ($input['url'] ?? '')),
            'parent_id' => (int) ($input['parent_id'] ?? 0),
            'item_type' => 'link',
            'target' => trim((string) ($input['target'] ?? '')),
            'display_style' => trim((string) ($input['display_style'] ?? 'list')),
            'group_title' => trim((string) ($input['group_title'] ?? '')),
            'column_key' => trim((string) ($input['column_key'] ?? '')),
            'sort_order' => trim((string) ($input['sort_order'] ?? '0')),
            'is_enabled' => isset($input['is_enabled']) ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function validateItem(array $item): ?string
    {
        if ((string) $item['label'] === '') {
            return 'Label is required.';
        }

        if ((string) $item['url'] === '') {
            return 'URL is required.';
        }

        if (!is_numeric((string) $item['sort_order']) || (int) $item['sort_order'] < 0) {
            return 'Sort order must be a non-negative whole number.';
        }

        if (!in_array((string) ($item['target'] ?? ''), ['', '_blank'], true)) {
            return 'Choose a valid link target.';
        }

        if (!in_array((string) ($item['display_style'] ?? 'list'), ['list', 'mega'], true)) {
            return 'Choose a valid dropdown presentation.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyItem(): array
    {
        return [
            'label' => '',
            'url' => '',
            'parent_id' => 0,
            'item_type' => 'link',
            'target' => '',
            'display_style' => 'list',
            'group_title' => '',
            'column_key' => '',
            'sort_order' => 0,
            'is_enabled' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(array $data): string
    {
        return $this->renderAdmin('admin-navigation-form', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    private function buildGroupTitleOptions(int $menuId): array
    {
        return $this->buildMetaOptions($menuId, 'group_title');
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    private function buildColumnKeyOptions(int $menuId): array
    {
        return $this->buildMetaOptions($menuId, 'column_key');
    }

    /**
     * @return array<int, array{label: string, options: array<int, array<string, mixed>>}>
     */
    private function buildMetaOptions(int $menuId, string $field): array
    {
        $items = $this->navigationService->listFlatMenuItems($menuId, false);
        $choices = [];

        foreach ($items as $item) {
            $value = trim((string) ($item[$field] ?? ''));

            if ($value !== '') {
                $choices[$value] = true;
            }
        }

        if ($choices === []) {
            return [];
        }

        ksort($choices);
        $options = array_map(
            static fn (string $value): array => ['label' => $value, 'value' => $value],
            array_keys($choices)
        );

        return [
            [
                'label' => 'Existing Values',
                'options' => $options,
            ],
        ];
    }
}
