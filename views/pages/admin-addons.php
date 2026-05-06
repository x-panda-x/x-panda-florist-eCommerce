<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Catalog Extras</p>
            <h2 class="admin-title">Add-Ons</h2>
            <p class="admin-subtitle">Manage gift-ready extras that can be assigned to products and carried into live orders.</p>
        </div>
        <a href="/admin/addons/create" class="admin-button">Add Add-On</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Price</th>
                <th>Status</th>
                <th>Sort</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($addons)): ?>
                <tr>
                    <td colspan="7">No add-ons created yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($addons as $addon): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($addon['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars((string) ($addon['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($addon['description'])): ?>
                                <div class="admin-note"><?php echo htmlspecialchars((string) ($addon['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($addon['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format((float) ($addon['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="admin-status-pill"><?php echo !empty($addon['is_active']) ? 'Active' : 'Inactive'; ?></span></td>
                        <td><?php echo htmlspecialchars((string) ($addon['sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="/admin/addons/edit?id=<?php echo urlencode((string) ($addon['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="/admin/addons/delete" onsubmit="return confirm('Delete this add-on?');" style="display:inline-block;margin-left:1rem;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($addon['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
