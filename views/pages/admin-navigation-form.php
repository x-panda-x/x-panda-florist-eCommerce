<?php $item = is_array($item ?? null) ? $item : []; ?>
<?php $menu = is_array($menu ?? null) ? $menu : []; ?>
<?php $parentOptions = is_array($parentOptions ?? null) ? $parentOptions : []; ?>
<?php $linkOptions = is_array($linkOptions ?? null) ? $linkOptions : []; ?>
<?php $groupTitleOptions = is_array($groupTitleOptions ?? null) ? $groupTitleOptions : []; ?>
<?php $columnKeyOptions = is_array($columnKeyOptions ?? null) ? $columnKeyOptions : []; ?>
<?php require_once BASE_PATH . '/views/components/admin-selection-controls.php'; ?>

<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Navigation Item'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Navigation Item</h2>
    <p class="admin-subtitle">Set where this menu item goes, where it appears, and whether it is visible.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '/admin/navigation'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="navigation_menu_id" value="<?php echo htmlspecialchars((string) ($menu['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($itemId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="admin-card admin-form-section admin-form-section--website" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Basic Link Settings</h3>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field">
                    <label for="label">Menu Label</label>
                    <input id="label" name="label" type="text" required value="<?php echo htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <?php
                admin_selection_render_hybrid_field([
                    'id' => 'url',
                    'name' => 'url',
                    'label' => 'Link Destination',
                    'value' => (string) ($item['url'] ?? ''),
                    'options' => $linkOptions,
                    'empty_label' => 'Choose a page, product, category, or occasion',
                    'custom_label' => 'Custom URL',
                    'custom_input_label' => 'Custom URL',
                    'placeholder' => '/best-sellers',
                    'required' => true,
                    'note' => 'Use Custom URL only when the destination is not listed.',
                ]);
                ?>

                <div class="admin-field">
                    <label for="parent_id">Parent Item</label>
                    <select id="parent_id" name="parent_id">
                        <option value="0">Top-level item</option>
                        <?php foreach ($parentOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars((string) ($option['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($item['parent_id'] ?? 0) === (int) ($option['id'] ?? 0) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) ($option['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="target">Open Link In</label>
                    <select id="target" name="target">
                        <option value="" <?php echo (string) ($item['target'] ?? '') === '' ? 'selected' : ''; ?>>Same window</option>
                        <option value="_blank" <?php echo (string) ($item['target'] ?? '') === '_blank' ? 'selected' : ''; ?>>New tab</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="admin-card admin-form-section admin-form-section--content" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Menu Structure</h3>
            <p class="admin-section-subtitle">Use these when building grouped dropdowns and mega menus.</p>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field">
                    <label for="display_style">Dropdown Style</label>
                    <select id="display_style" name="display_style">
                        <option value="list" <?php echo (string) ($item['display_style'] ?? 'list') === 'list' ? 'selected' : ''; ?>>Standard dropdown list</option>
                        <option value="mega" <?php echo (string) ($item['display_style'] ?? 'list') === 'mega' ? 'selected' : ''; ?>>Mega menu grouping</option>
                    </select>
                    <small class="admin-note">Used by top-level items. Child items ignore this field.</small>
                </div>

                <div class="admin-field">
                    <label for="sort_order">Order In Menu</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($item['sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <?php
                admin_selection_render_hybrid_field([
                    'id' => 'group_title',
                    'name' => 'group_title',
                    'label' => 'Group Title',
                    'value' => (string) ($item['group_title'] ?? ''),
                    'options' => $groupTitleOptions,
                    'empty_label' => 'No group title',
                    'custom_label' => 'Custom group title',
                    'custom_input_label' => 'Custom Group Title',
                    'placeholder' => 'Featured Occasions',
                    'note' => 'Optional for child links in mega menus.',
                ]);
                ?>

                <?php
                admin_selection_render_hybrid_field([
                    'id' => 'column_key',
                    'name' => 'column_key',
                    'label' => 'Column Group',
                    'value' => (string) ($item['column_key'] ?? ''),
                    'options' => $columnKeyOptions,
                    'empty_label' => 'Default column',
                    'custom_label' => 'Custom column key',
                    'custom_input_label' => 'Custom Column Key',
                    'placeholder' => 'featured-occasions',
                    'note' => 'Child links with the same column key are grouped together.',
                ]);
                ?>
            </div>
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label class="admin-checkbox">
                <input id="is_enabled" name="is_enabled" type="checkbox" value="1" <?php echo !empty($item['is_enabled']) ? 'checked' : ''; ?>>
                <span>Show this item in storefront navigation</span>
            </label>
        </div>

        <div style="grid-column:1 / -1;display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update Navigation Item' : 'Save Navigation Item'; ?></button>
            <a href="/admin/navigation" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>
