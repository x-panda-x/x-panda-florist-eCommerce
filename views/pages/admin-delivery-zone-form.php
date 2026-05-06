<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Delivery Zone'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Serviceable ZIP and fee management.</h2>
    <p class="admin-subtitle">This form keeps the current delivery-zone CRUD behavior intact.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($zoneId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="admin-grid cols-2">
            <div class="admin-field">
                <label for="zip_code">ZIP Code</label>
                <input id="zip_code" name="zip_code" type="text" inputmode="numeric" maxlength="5" required value="<?php echo htmlspecialchars((string) ($zone['zip_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-field">
                <label for="delivery_fee">Delivery Fee</label>
                <input id="delivery_fee" name="delivery_fee" type="number" min="0" step="0.01" required value="<?php echo htmlspecialchars((string) ($zone['delivery_fee'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <label class="admin-checkbox" style="margin-top:0.5rem;">
            <input name="is_active" type="checkbox" value="1" <?php echo !empty($zone['is_active']) ? 'checked' : ''; ?>>
            <span>Zone is active</span>
        </label>

        <div style="margin-top:1rem;display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update Delivery Zone' : 'Save Delivery Zone'; ?></button>
            <a href="<?php echo htmlspecialchars((string) ($indexPath ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>
