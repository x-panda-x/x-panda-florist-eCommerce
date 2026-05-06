<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Website</p>
            <h2 class="admin-title">Banners</h2>
            <p class="admin-subtitle">Manage global promo strip and other banner placements without changing storefront templates.</p>
        </div>
        <a href="/admin/banners/create" class="admin-button">Add Banner</a>
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
                <th>Banner</th>
                <th>Placement</th>
                <th>Schedule</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($banners)): ?>
                <tr>
                    <td colspan="5">No banners created yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($banners as $banner): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars((string) ($banner['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="admin-note"><?php echo htmlspecialchars((string) ($banner['banner_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td>
                            <div class="admin-note">Page: <?php echo htmlspecialchars((string) ($banner['page_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="admin-note">Placement: <?php echo htmlspecialchars((string) ($banner['placement'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td>
                            <div class="admin-note">Starts: <?php echo !empty($banner['starts_at']) ? htmlspecialchars((string) ($banner['starts_at'] ?? ''), ENT_QUOTES, 'UTF-8') : 'Immediate'; ?></div>
                            <div class="admin-note">Ends: <?php echo !empty($banner['ends_at']) ? htmlspecialchars((string) ($banner['ends_at'] ?? ''), ENT_QUOTES, 'UTF-8') : 'No expiration'; ?></div>
                        </td>
                        <td><span class="admin-status-pill"><?php echo !empty($banner['is_enabled']) ? 'Enabled' : 'Disabled'; ?></span></td>
                        <td>
                            <a href="/admin/banners/edit?id=<?php echo urlencode((string) ($banner['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="/admin/banners/delete" onsubmit="return confirm('Delete this banner?');" style="display:inline-block;margin-left:1rem;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($banner['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
