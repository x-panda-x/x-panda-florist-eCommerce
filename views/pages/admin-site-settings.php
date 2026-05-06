<div class="admin-form-shell">
    <p class="admin-kicker">Website</p>
    <h2 class="admin-title">Store Details And Contact</h2>
    <p class="admin-subtitle">Update the public business details customers see across the live site and emails.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <nav class="admin-section-nav" aria-label="Site settings sections">
        <ul class="admin-section-nav__list">
            <li><a href="#site-settings-identity">Store Identity</a></li>
            <li><a href="#site-settings-email">Email Delivery</a></li>
            <li><a href="#site-settings-support">Support And Business</a></li>
            <li><a href="#site-settings-social">Social Links</a></li>
            <li><a href="/admin/site-settings/card-note">Print Card Note</a></li>
        </ul>
    </nav>

    <form method="post" action="/admin/site-settings" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>

        <div id="site-settings-identity" class="admin-card admin-form-section admin-form-section--website" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Store Identity</h3>
            <p class="admin-section-subtitle">Use your final customer-facing brand name only: Lily and Rose.</p>
            <div class="admin-current-list">
                <div><strong>Current Store Name:</strong> <?php echo htmlspecialchars((string) ($settings['store_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Current Public Base URL:</strong> <?php echo htmlspecialchars((string) ($settings['public_base_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field" style="grid-column:1 / -1;">
                    <label for="store_name">Store Name</label>
                    <input id="store_name" name="store_name" type="text" value="<?php echo htmlspecialchars((string) ($settings['store_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field">
                    <label for="store_phone">Store Phone</label>
                    <input id="store_phone" name="store_phone" type="text" value="<?php echo htmlspecialchars((string) ($settings['store_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field">
                    <label for="store_email">Store Email</label>
                    <input id="store_email" name="store_email" type="email" value="<?php echo htmlspecialchars((string) ($settings['store_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-field" style="grid-column:1 / -1;">
                    <label for="public_base_url">Public Base URL</label>
                    <input id="public_base_url" name="public_base_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['public_base_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.lilyandroseflorist.com">
                    <p class="admin-note" style="margin-top:0.45rem;">Used in customer links for password reset and account emails.</p>
                </div>
            </div>
        </div>

        <div id="site-settings-email" class="admin-card admin-form-section admin-form-section--status" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Email Delivery</h3>
            <p class="admin-section-subtitle">Current mode is <?php echo htmlspecialchars((string) ($settings['email_delivery_mode'] ?? 'log_only'), ENT_QUOTES, 'UTF-8'); ?>.</p>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="email_delivery_mode">Email Mode</label>
                    <select id="email_delivery_mode" name="email_delivery_mode">
                        <option value="log_only" <?php echo (string) ($settings['email_delivery_mode'] ?? 'log_only') === 'log_only' ? 'selected' : ''; ?>>Log Only</option>
                        <option value="php_mail" <?php echo (string) ($settings['email_delivery_mode'] ?? '') === 'php_mail' ? 'selected' : ''; ?>>PHP mail()</option>
                        <option value="smtp" <?php echo (string) ($settings['email_delivery_mode'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                    </select>
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="smtp_host">SMTP Host</label>
                    <input id="smtp_host" name="smtp_host" type="text" value="<?php echo htmlspecialchars((string) ($settings['smtp_host'] ?? 'smtp.gmail.com'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="smtp_port">SMTP Port</label>
                    <input id="smtp_port" name="smtp_port" type="number" min="1" value="<?php echo htmlspecialchars((string) ($settings['smtp_port'] ?? '587'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="smtp_encryption">SMTP Encryption</label>
                    <select id="smtp_encryption" name="smtp_encryption">
                        <option value="tls" <?php echo (string) ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo (string) ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo (string) ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="smtp_username">SMTP Username</label>
                    <input id="smtp_username" name="smtp_username" type="email" value="<?php echo htmlspecialchars((string) ($settings['smtp_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="smtp_password">SMTP App Password</label>
                    <input id="smtp_password" name="smtp_password" type="password" value="" placeholder="Leave blank to keep the saved app password">
                    <p class="admin-note" style="margin-top:0.45rem;">For authenticated SMTP, enter the provider password. For host relays that do not require auth, leave username and password blank.</p>
                </div>
            </div>
        </div>

        <div id="site-settings-support" class="admin-card admin-form-section admin-form-section--content" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Support And Business Text</h3>
            <p class="admin-section-subtitle">These blocks appear in contact and footer support areas.</p>
            <div class="admin-field" style="margin-top:1rem;">
                <label for="store_address">Store Address</label>
                <textarea id="store_address" name="store_address" rows="3"><?php echo htmlspecialchars((string) ($settings['store_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="admin-field">
                <label for="support_text">Support Text</label>
                <textarea id="support_text" name="support_text" rows="4"><?php echo htmlspecialchars((string) ($settings['support_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="admin-field" style="margin-bottom:0;">
                <label for="business_info">Business Info</label>
                <textarea id="business_info" name="business_info" rows="5"><?php echo htmlspecialchars((string) ($settings['business_info'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div id="site-settings-social" class="admin-card admin-form-section admin-form-section--website" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Social Links</h3>
            <p class="admin-section-subtitle">Add full URLs for social channels you want visible in the storefront footer.</p>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="instagram_url">Instagram URL</label>
                    <input id="instagram_url" name="instagram_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['instagram_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="facebook_url">Facebook URL</label>
                    <input id="facebook_url" name="facebook_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['facebook_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="x_url">X URL</label>
                    <input id="x_url" name="x_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['x_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label for="tiktok_url">TikTok URL</label>
                    <input id="tiktok_url" name="tiktok_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['tiktok_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>

        <div style="grid-column:1 / -1;">
            <button type="submit" class="admin-button">Save Site Details</button>
        </div>
    </form>
</div>
