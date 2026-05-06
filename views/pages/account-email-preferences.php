<?php $customer = is_array($customer ?? null) ? $customer : []; ?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-card" style="max-width:700px;">
        <div class="account-card-header">
            <h2>Email Preferences</h2>
            <p class="site-note">Manage marketing, reminder, and order-related email flags.</p>
        </div>

        <form method="post" action="/account/email-preferences" class="stack-lg">
            <?php echo csrf_field(); ?>
            <div class="stack-md">
                <label class="site-note" style="display:flex;gap:1.5rem;align-items:flex-start;padding:1.5rem;background:rgba(255,255,255,0.8);border:1px solid var(--color-gray-light);border-radius:6px;cursor:pointer;transition:border-color 0.2s;">
                    <input type="checkbox" name="marketing_opt_in" value="1" <?php echo !empty($customer['marketing_opt_in']) ? 'checked' : ''; ?> style="width:1.25rem;height:1.25rem;margin-top:0.1rem;padding:0;">
                    <div class="stack-sm" style="gap:0.35rem;">
                        <strong style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.85rem;">Marketing Emails</strong>
                        <span>Send me marketing emails about seasonal offers and florist promotions.</span>
                    </div>
                </label>
                <label class="site-note" style="display:flex;gap:1.5rem;align-items:flex-start;padding:1.5rem;background:rgba(255,255,255,0.8);border:1px solid var(--color-gray-light);border-radius:6px;cursor:pointer;transition:border-color 0.2s;">
                    <input type="checkbox" name="reminder_email_opt_in" value="1" <?php echo !empty($customer['reminder_email_opt_in']) ? 'checked' : ''; ?> style="width:1.25rem;height:1.25rem;margin-top:0.1rem;padding:0;">
                    <div class="stack-sm" style="gap:0.35rem;">
                        <strong style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.85rem;">Reminder Emails</strong>
                        <span>Send me reminder emails for future customer reminder features.</span>
                    </div>
                </label>
                <label class="site-note" style="display:flex;gap:1.5rem;align-items:flex-start;padding:1.5rem;background:rgba(255,255,255,0.8);border:1px solid var(--color-gray-light);border-radius:6px;cursor:pointer;transition:border-color 0.2s;">
                    <input type="checkbox" name="order_email_opt_in" value="1" <?php echo !empty($customer['order_email_opt_in']) ? 'checked' : ''; ?> style="width:1.25rem;height:1.25rem;margin-top:0.1rem;padding:0;">
                    <div class="stack-sm" style="gap:0.35rem;">
                        <strong style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.85rem;">Order Emails</strong>
                        <span>Send me order-related customer emails about purchases and order updates.</span>
                    </div>
                </label>
            </div>
            <div style="margin-top:1.5rem;">
                <button type="submit" class="btn" style="min-width: 240px;">Save Preferences</button>
            </div>
        </form>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
