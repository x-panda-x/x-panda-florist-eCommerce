<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Catalog Structure</p>
            <h2 class="admin-title"><?php echo htmlspecialchars((string) ($itemLabelPlural ?? 'Items'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="admin-subtitle">Manage <?php echo htmlspecialchars(strtolower((string) ($itemLabelPlural ?? 'items')), ENT_QUOTES, 'UTF-8'); ?> used across the live product catalog.</p>
        </div>
        <a href="<?php echo htmlspecialchars((string) ($createPath ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-button">Add <?php echo htmlspecialchars((string) ($itemLabelSingular ?? 'Item'), ENT_QUOTES, 'UTF-8'); ?></a>
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
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="4">No <?php echo htmlspecialchars(strtolower((string) ($itemLabelPlural ?? 'items')), ENT_QUOTES, 'UTF-8'); ?> yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars((string) ($item['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars((string) ($editBasePath ?? '#'), ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo urlencode((string) ($item['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="<?php echo htmlspecialchars((string) ($deletePath ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Delete this <?php echo htmlspecialchars(strtolower((string) ($itemLabelSingular ?? 'item')), ENT_QUOTES, 'UTF-8'); ?>?');" style="display:inline-block;margin-left:1rem;">
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
