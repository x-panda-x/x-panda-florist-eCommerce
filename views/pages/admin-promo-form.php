<?php
$startsAtValue = '';
if (!empty($promoCode['starts_at'])) {
    $startsAtValue = str_replace(' ', 'T', substr((string) $promoCode['starts_at'], 0, 16));
}

$expiresAtValue = '';
if (!empty($promoCode['expires_at'])) {
    $expiresAtValue = str_replace(' ', 'T', substr((string) $promoCode['expires_at'], 0, 16));
}
?>

<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Create Promo Code'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Promo code details and availability.</h2>
    <p class="admin-subtitle">Set discount type, value, schedule, and usage rules. Percentage off is the recommended default for most promotions.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '/admin/promo-codes'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($promoId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="admin-field">
            <label for="code">Code</label>
            <input id="code" name="code" type="text" required value="<?php echo htmlspecialchars((string) ($promoCode['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="discount_type">Discount Type</label>
            <select id="discount_type" name="discount_type">
                <?php foreach (($discountTypes ?? []) as $discountType): ?>
                    <option value="<?php echo htmlspecialchars((string) $discountType, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($promoCode['discount_type'] ?? '') === (string) $discountType ? 'selected' : ''; ?>>
                        <?php
                        $discountTypeLabel = $discountType === 'percentage'
                            ? 'Percentage Off (Recommended)'
                            : 'Fixed Amount Off ($)';
                        echo htmlspecialchars((string) $discountTypeLabel, ENT_QUOTES, 'UTF-8');
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p id="discount_type_help" class="admin-note" style="margin-top:0.4rem;">Percentage off applies to the current cart subtotal. Fixed amount off subtracts a flat dollar value.</p>
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars((string) ($promoCode['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="admin-field">
            <label id="discount_value_label" for="discount_value">Discount Value</label>
            <input id="discount_value" name="discount_value" type="number" min="0.01" step="0.01" required value="<?php echo htmlspecialchars((string) ($promoCode['discount_value'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <p id="discount_value_help" class="admin-note" style="margin-top:0.4rem;">Enter 25 for a 25% discount.</p>
        </div>

        <div class="admin-field">
            <label for="minimum_subtotal">Minimum Subtotal</label>
            <input id="minimum_subtotal" name="minimum_subtotal" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars((string) ($promoCode['minimum_subtotal'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <p class="admin-note" style="margin-top:0.4rem;">Set to 0 for no minimum. Example: 100 means the cart subtotal must be at least $100.00.</p>
        </div>

        <div class="admin-field">
            <label for="starts_at">Starts At</label>
            <input id="starts_at" name="starts_at" type="datetime-local" value="<?php echo htmlspecialchars($startsAtValue, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="expires_at">Ends At</label>
            <input id="expires_at" name="expires_at" type="datetime-local" value="<?php echo htmlspecialchars($expiresAtValue, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="usage_limit">Usage Limit</label>
            <input id="usage_limit" name="usage_limit" type="number" min="1" step="1" value="<?php echo htmlspecialchars((string) ($promoCode['usage_limit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="times_used">Times Used</label>
            <input id="times_used" name="times_used" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($promoCode['times_used'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" readonly>
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label class="admin-checkbox">
                <input id="is_active" name="is_active" type="checkbox" value="1" <?php echo !empty($promoCode['is_active']) ? 'checked' : ''; ?>>
                <span>Active and available to customers during the valid schedule window</span>
            </label>
        </div>

        <div style="grid-column:1 / -1;display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update Promo Code' : 'Save Promo Code'; ?></button>
            <a href="/admin/promo-codes" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
(() => {
    const discountType = document.getElementById('discount_type');
    const discountValue = document.getElementById('discount_value');
    const discountValueLabel = document.getElementById('discount_value_label');
    const discountValueHelp = document.getElementById('discount_value_help');

    if (!discountType || !discountValue || !discountValueLabel || !discountValueHelp) {
        return;
    }

    const syncDiscountField = () => {
        if (discountType.value === 'percentage') {
            discountValueLabel.textContent = 'Percentage Off';
            discountValueHelp.textContent = 'Enter 25 for a 25% discount. Decimals are allowed (for example, 12.5 for 12.5% off).';
            discountValue.placeholder = '25';
            discountValue.max = '100';
            return;
        }

        discountValueLabel.textContent = 'Fixed Amount Off ($)';
        discountValueHelp.textContent = 'Enter a flat dollar amount such as 5.00 for $5 off.';
        discountValue.placeholder = '5.00';
        discountValue.removeAttribute('max');
    };

    discountType.addEventListener('change', syncDiscountField);
    syncDiscountField();
})();
</script>
