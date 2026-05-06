<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Create Add-On'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Add-on details and availability.</h2>
    <p class="admin-subtitle">Create or update an extra that can be assigned to products, selected on the storefront, and saved into orders.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '/admin/addons'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($addonId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required value="<?php echo htmlspecialchars((string) ($addon['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="slug">Slug</label>
            <input id="slug" name="slug" type="text" required value="<?php echo htmlspecialchars((string) ($addon['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars((string) ($addon['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="admin-field">
            <label for="price">Price</label>
            <input id="price" name="price" type="number" min="0" step="0.01" required value="<?php echo htmlspecialchars((string) ($addon['price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="sort_order">Sort Order</label>
            <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($addon['sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label class="admin-checkbox">
                <input id="is_active" name="is_active" type="checkbox" value="1" <?php echo !empty($addon['is_active']) ? 'checked' : ''; ?>>
                <span>Active and available on assigned product pages</span>
            </label>
        </div>

        <div style="grid-column:1 / -1;display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update Add-On' : 'Save Add-On'; ?></button>
            <a href="/admin/addons" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>
