<div class="admin-form-shell">
    <p class="admin-kicker">Operations</p>
    <h2 class="admin-title">Runtime Store Settings</h2>
    <p class="admin-subtitle">Use this page for day-to-day operating values. Brand and full contact settings are under Site Settings.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="admin-current-list">
        <div><strong>Current Store Name:</strong> <?php echo htmlspecialchars((string) ($settings['store_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Current Email Mode:</strong> <?php echo htmlspecialchars((string) ($settings['email_delivery_mode'] ?? 'log_only'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Current Same-Day Cutoff:</strong> <?php echo htmlspecialchars((string) ($settings['same_day_cutoff'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Current Sales Tax Rate:</strong> <?php echo htmlspecialchars((string) ($settings['sales_tax_rate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <form method="post" action="/admin/settings" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>

        <div class="admin-card admin-form-section admin-form-section--status" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Store Runtime Basics</h3>
            <p class="admin-section-subtitle">These values affect order cutoffs, tax, and transactional runtime behavior.</p>

            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field" style="grid-column:1 / -1;">
                    <label for="store_name">Store Name</label>
                    <input id="store_name" name="store_name" type="text" value="<?php echo htmlspecialchars((string) ($settings['store_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field">
                    <label for="store_email">Store Email</label>
                    <input id="store_email" name="store_email" type="email" value="<?php echo htmlspecialchars((string) ($settings['store_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field">
                    <label for="store_phone">Store Phone</label>
                    <input id="store_phone" name="store_phone" type="text" value="<?php echo htmlspecialchars((string) ($settings['store_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field">
                    <label for="email_delivery_mode">Email Delivery Mode</label>
                    <select id="email_delivery_mode" name="email_delivery_mode">
                        <option value="log_only" <?php echo (string) ($settings['email_delivery_mode'] ?? 'log_only') === 'log_only' ? 'selected' : ''; ?>>Log Only</option>
                        <option value="php_mail" <?php echo (string) ($settings['email_delivery_mode'] ?? '') === 'php_mail' ? 'selected' : ''; ?>>PHP mail()</option>
                        <option value="smtp" <?php echo (string) ($settings['email_delivery_mode'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                    </select>
                    <p class="admin-note" style="margin-top:0.45rem;">SMTP host, port, and password stay in Site Settings.</p>
                </div>

                <div class="admin-field">
                    <label for="same_day_cutoff">Same-Day Cutoff</label>
                    <input id="same_day_cutoff" name="same_day_cutoff" type="time" value="<?php echo htmlspecialchars((string) ($settings['same_day_cutoff'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field" style="margin-bottom:0;">
                    <label for="sales_tax_rate">Sales Tax Rate (0 to 1)</label>
                    <input id="sales_tax_rate" name="sales_tax_rate" type="number" min="0" max="1" step="0.0001" value="<?php echo htmlspecialchars((string) ($settings['sales_tax_rate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>

        <div style="grid-column:1 / -1;">
            <button type="submit" class="admin-button">Save Runtime Settings</button>
        </div>
    </form>
</div>
