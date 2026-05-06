<div class="admin-card">
    <p class="admin-kicker">Customer Management</p>
    <h2 class="admin-title">Customers</h2>
    <p class="admin-subtitle">Review customer accounts, search by name or email, and open each account for orders, saved addresses, reminders, and account status.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="admin-card" style="margin-top:1rem;">
    <form method="get" action="/admin/customers" class="admin-grid cols-3" style="align-items:end;">
        <div class="admin-field" style="grid-column:1 / span 2;">
            <label for="customer-search">Search Customers</label>
            <input id="customer-search" type="text" name="q" value="<?php echo htmlspecialchars((string) ($searchQuery ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by full name or email">
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <button type="submit" class="admin-button">Search</button>
            <?php if (!empty($searchQuery)): ?>
                <a href="/admin/customers" class="admin-button-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-table-wrap" style="margin-top:1rem;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Orders</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="7"><?php echo !empty($searchQuery) ? 'No customers matched your search.' : 'No customer accounts found yet.'; ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($customer['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars((string) ($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($customer['phone'])): ?>
                                <div class="admin-note"><?php echo htmlspecialchars((string) ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="admin-status-pill"><?php echo !empty($customer['is_active']) ? 'active' : 'inactive'; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars((string) ((int) ($customer['order_count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($customer['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="display:flex;gap:0.9rem;align-items:center;flex-wrap:wrap;">
                            <a href="/admin/customers/view?id=<?php echo urlencode((string) ($customer['id'] ?? 0)); ?>" class="admin-text-button">View</a>
                            <form method="post" action="/admin/customers/toggle-status" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($customer['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="return_to" value="/admin/customers">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;padding:0;">
                                    <?php echo !empty($customer['is_active']) ? 'Disable' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
