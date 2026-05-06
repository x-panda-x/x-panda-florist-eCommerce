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
    <form method="post" action="/admin/products/delete-selected" id="bulk-delete-form" style="display:none;">
        <?php echo csrf_field(); ?>
        <div id="bulk-delete-inputs"></div>
    </form>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;margin:0 0 .75rem 0;">
        <div style="display:flex;gap:.5rem;align-items:center;">
            <span id="bulk-selected-count" class="admin-status-pill">Selected: 0</span>
            <button type="button" id="bulk-select-visible" class="admin-text-button" style="border:0;background:none;">Select Visible</button>
            <button type="button" id="bulk-clear-selection" class="admin-text-button" style="border:0;background:none;">Clear Selection</button>
        </div>
        <button type="button" id="bulk-delete-submit" class="admin-button" style="background:#8b3c39;" disabled>Delete Selected Products</button>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:42px;"><input type="checkbox" id="bulk-select-all" aria-label="Select all visible products"></th>
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
                    <td colspan="7">No products yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="bulk-product-checkbox"
                                name="product_ids[]"
                                value="<?php echo htmlspecialchars((string) ($product['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                aria-label="Select product <?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </td>
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
<script>
(() => {
    const checkboxes = Array.from(document.querySelectorAll('.bulk-product-checkbox'));
    const selectedCount = document.getElementById('bulk-selected-count');
    const selectVisible = document.getElementById('bulk-select-visible');
    const clearSelection = document.getElementById('bulk-clear-selection');
    const selectAll = document.getElementById('bulk-select-all');
    const form = document.getElementById('bulk-delete-form');
    const formInputs = document.getElementById('bulk-delete-inputs');
    const submit = document.getElementById('bulk-delete-submit');

    const refresh = () => {
        const count = checkboxes.filter((checkbox) => checkbox.checked).length;
        selectedCount.textContent = `Selected: ${count}`;
        submit.disabled = count === 0;
        if (selectAll) {
            selectAll.checked = count > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refresh));
    if (selectVisible) {
        selectVisible.addEventListener('click', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = true; });
            refresh();
        });
    }
    if (clearSelection) {
        clearSelection.addEventListener('click', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = false; });
            refresh();
        });
    }
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
            refresh();
        });
    }
    if (submit && form && formInputs) {
        submit.addEventListener('click', () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
            const count = selected.length;
            if (count <= 0) {
                return;
            }
            if (!confirm(`Delete ${count} selected product(s)? Products used in orders are protected and cannot be deleted.`)) {
                return;
            }
            formInputs.innerHTML = '';
            selected.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'product_ids[]';
                input.value = id;
                formInputs.appendChild(input);
            });
            form.submit();
        });
    }
    refresh();
})();
</script>
