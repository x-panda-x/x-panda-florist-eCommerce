<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Create Item'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Name And URL</h2>
    <p class="admin-subtitle">Use a clear name for admins and a short slug for URLs.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($taxonomyId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="admin-field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required value="<?php echo htmlspecialchars((string) ($taxonomy['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="slug">Slug (URL Part)</label>
            <input id="slug" name="slug" type="text" required value="<?php echo htmlspecialchars((string) ($taxonomy['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="admin-note">Use lowercase words with dashes, for example: spring-flowers</small>
        </div>

        <div style="display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update ' . htmlspecialchars((string) ($itemLabelSingular ?? 'Item'), ENT_QUOTES, 'UTF-8') : 'Save ' . htmlspecialchars((string) ($itemLabelSingular ?? 'Item'), ENT_QUOTES, 'UTF-8'); ?></button>
            <a href="<?php echo htmlspecialchars((string) ($indexPath ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>
