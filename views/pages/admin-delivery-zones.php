<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Fulfillment</p>
            <h2 class="admin-title">Delivery Zones</h2>
            <p class="admin-subtitle">Manage serviceable ZIP codes and their delivery fees with a cleaner table layout.</p>
        </div>
        <a href="/admin/delivery-zones/create" class="admin-button">Add Delivery Zone</a>
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
                <th>ZIP Code</th>
                <th>Delivery Fee</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($zones)): ?>
                <tr>
                    <td colspan="5">No delivery zones yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($zones as $zone): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($zone['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo htmlspecialchars((string) ($zone['zip_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td>$<?php echo htmlspecialchars(number_format((float) ($zone['delivery_fee'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="admin-status-pill"><?php echo !empty($zone['is_active']) ? 'Active' : 'Inactive'; ?></span></td>
                        <td>
                            <a href="/admin/delivery-zones/edit?id=<?php echo urlencode((string) ($zone['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="/admin/delivery-zones/delete" onsubmit="return confirm('Delete this delivery zone?');" style="display:inline-block;margin-left:1rem;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($zone['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
