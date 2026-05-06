<div class="admin-card">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Checkout Pricing</p>
            <h2 class="admin-title">Promo Codes</h2>
            <p class="admin-subtitle">Manage promo codes for cart and checkout. Percentage discounts are recommended by default, with fixed-amount discounts available when needed.</p>
        </div>
        <a href="/admin/promo-codes/create" class="admin-button">Add Promo Code</a>
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
                <th>Code</th>
                <th>Discount</th>
                <th>Minimum</th>
                <th>Schedule</th>
                <th>Usage</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($promoCodes)): ?>
                <tr>
                    <td colspan="7">No promo codes created yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($promoCodes as $promoCode): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars((string) ($promoCode['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($promoCode['description'])): ?>
                                <div class="admin-note"><?php echo htmlspecialchars((string) ($promoCode['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $discountType = (string) ($promoCode['discount_type'] ?? 'fixed_amount'); ?>
                            <?php if ($discountType === 'percentage'): ?>
                                Percentage Off
                                <div class="admin-note"><?php echo htmlspecialchars(rtrim(rtrim(number_format((float) ($promoCode['discount_value'] ?? 0), 2), '0'), '.'), ENT_QUOTES, 'UTF-8'); ?>% off</div>
                            <?php else: ?>
                                Fixed Amount Off
                                <div class="admin-note">$<?php echo htmlspecialchars(number_format((float) ($promoCode['discount_value'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?> off</div>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo htmlspecialchars(number_format((float) ($promoCode['minimum_subtotal'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div class="admin-note">Starts: <?php echo !empty($promoCode['starts_at']) ? htmlspecialchars((string) ($promoCode['starts_at'] ?? ''), ENT_QUOTES, 'UTF-8') : 'Immediate'; ?></div>
                            <div class="admin-note">Ends: <?php echo !empty($promoCode['expires_at']) ? htmlspecialchars((string) ($promoCode['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8') : 'No expiration'; ?></div>
                        </td>
                        <td>
                            <div class="admin-note">Used: <?php echo htmlspecialchars((string) ((int) ($promoCode['times_used'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="admin-note">Limit: <?php echo ($promoCode['usage_limit'] ?? null) !== null ? htmlspecialchars((string) ($promoCode['usage_limit'] ?? ''), ENT_QUOTES, 'UTF-8') : 'Unlimited'; ?></div>
                        </td>
                        <td><span class="admin-status-pill"><?php echo !empty($promoCode['is_active']) ? 'Active' : 'Inactive'; ?></span></td>
                        <td>
                            <a href="/admin/promo-codes/edit?id=<?php echo urlencode((string) ($promoCode['id'] ?? '')); ?>" class="admin-text-button">Edit</a>
                            <form method="post" action="/admin/promo-codes/delete" onsubmit="return confirm('Delete this promo code?');" style="display:inline-block;margin-left:1rem;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($promoCode['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
