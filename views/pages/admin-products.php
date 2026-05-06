<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Catalog</p>
            <h2 class="admin-title">Products</h2>
            <p class="admin-subtitle">Manage live product records, pricing foundations, variants, categories, occasions, and images.</p>
        </div>
        <a href="/admin/products/create" class="admin-button">Add Product</a>
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
                <th>Base Price</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6">No products yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($product['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars((string) ($product['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format((float) ($product['base_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="admin-status-pill"><?php echo !empty($product['is_featured']) ? 'Featured' : 'Standard'; ?></span></td>
                        <td>
                            <a href="/admin/products/edit?id=<?php echo urlencode((string) ($product['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="/admin/products/delete" onsubmit="return confirm('Delete this product? Products used in orders are protected and cannot be deleted.');" style="display:inline-block;margin-left:1rem;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($product['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
