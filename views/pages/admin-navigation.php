<?php $menu = is_array($menu ?? null) ? $menu : []; ?>
<?php $items = is_array($items ?? null) ? $items : []; ?>
<?php
$itemLabelMap = [];
foreach ($items as $mapItem) {
    $mapId = (int) ($mapItem['id'] ?? 0);
    if ($mapId > 0) {
        $itemLabelMap[$mapId] = (string) ($mapItem['label'] ?? '');
    }
}
?>

<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Website</p>
            <h2 class="admin-title">Navigation</h2>
            <p class="admin-subtitle">Manage the storefront primary menu used by both the desktop header and mobile drawer.</p>
        </div>
        <a href="/admin/navigation/create" class="admin-button">Add Navigation Item</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="admin-card" style="margin-top:1rem;">
    <p class="admin-kicker">Active Menu</p>
    <h3 class="admin-title" style="font-size:1.6rem;"><?php echo htmlspecialchars((string) ($menu['name'] ?? 'Storefront Primary'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p class="admin-note">Menu key: <?php echo htmlspecialchars((string) ($menu['menu_key'] ?? 'storefront-primary'), ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Label</th>
                <th>URL</th>
                <th>Parent</th>
                <th>Structure</th>
                <th>Sort</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($items === []): ?>
                <tr>
                    <td colspan="7">No navigation items created yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (($item['parent_id'] ?? null) !== null): ?>
                                <div class="admin-note">Child item</div>
                            <?php else: ?>
                                <div class="admin-note">Top-level item</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($item['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php
                            $parentId = isset($item['parent_id']) ? (int) $item['parent_id'] : 0;
                            echo $parentId > 0
                                ? htmlspecialchars((string) ($itemLabelMap[$parentId] ?? ('Item #' . $parentId)), ENT_QUOTES, 'UTF-8')
                                : 'Top-level';
                            ?>
                        </td>
                        <td>
                            <?php if (($item['parent_id'] ?? null) === null): ?>
                                <div class="admin-note">Dropdown: <?php echo htmlspecialchars((string) ($item['display_style'] ?? 'list'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                                <div class="admin-note">Group: <?php echo htmlspecialchars((string) (($item['group_title'] ?? '') !== '' ? $item['group_title'] : 'None'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="admin-note">Column: <?php echo htmlspecialchars((string) (($item['column_key'] ?? '') !== '' ? $item['column_key'] : 'default'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <div class="admin-note">Target: <?php echo (string) ($item['target'] ?? '') === '_blank' ? 'New tab' : 'Same window'; ?></div>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($item['sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="admin-status-pill"><?php echo !empty($item['is_enabled']) ? 'Enabled' : 'Disabled'; ?></span></td>
                        <td>
                            <a href="/admin/navigation/edit?id=<?php echo urlencode((string) ($item['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="/admin/navigation/delete" onsubmit="return confirm('Delete this navigation item?');" style="display:inline-block;margin-left:1rem;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
