<div class="admin-form-shell">
    <p class="admin-kicker">Email</p>
    <h2 class="admin-title">Brand Email Identity</h2>
    <p class="admin-subtitle">Manage sender identity, support details, and footer copy used by automated emails.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/email-settings" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <div class="admin-card admin-form-section admin-form-section--website" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Sender Identity</h3>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field"><label for="store_name">Store / Brand Name</label><input id="store_name" name="store_name" type="text" value="<?php echo htmlspecialchars((string) ($settings['store_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="email_sender_name">Sender Display Name</label><input id="email_sender_name" name="email_sender_name" type="text" value="<?php echo htmlspecialchars((string) ($settings['email_sender_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="email_reply_to">Reply-To Email</label><input id="email_reply_to" name="email_reply_to" type="email" value="<?php echo htmlspecialchars((string) ($settings['email_reply_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="store_email">Store Contact Email</label><input id="store_email" name="store_email" type="email" value="<?php echo htmlspecialchars((string) ($settings['store_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
            </div>
        </div>
        <div class="admin-card admin-form-section admin-form-section--content" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Support And Footer</h3>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field"><label for="store_phone">Store Phone</label><input id="store_phone" name="store_phone" type="text" value="<?php echo htmlspecialchars((string) ($settings['store_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="public_base_url">Website URL</label><input id="public_base_url" name="public_base_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['public_base_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field" style="grid-column:1 / -1;"><label for="store_address">Store Address</label><textarea id="store_address" name="store_address" rows="3"><?php echo htmlspecialchars((string) ($settings['store_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="admin-field" style="grid-column:1 / -1;"><label for="email_support_message">Support Message</label><textarea id="email_support_message" name="email_support_message" rows="2"><?php echo htmlspecialchars((string) ($settings['email_support_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="admin-field" style="grid-column:1 / -1;"><label for="email_footer_text">Footer Text</label><textarea id="email_footer_text" name="email_footer_text" rows="2"><?php echo htmlspecialchars((string) ($settings['email_footer_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            </div>
        </div>
        <div class="admin-card admin-form-section admin-form-section--status" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Optional Social Links</h3>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field"><label for="instagram_url">Instagram</label><input id="instagram_url" name="instagram_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['instagram_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="facebook_url">Facebook</label><input id="facebook_url" name="facebook_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['facebook_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="x_url">X</label><input id="x_url" name="x_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['x_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="admin-field"><label for="tiktok_url">TikTok</label><input id="tiktok_url" name="tiktok_url" type="url" value="<?php echo htmlspecialchars((string) ($settings['tiktok_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
            </div>
        </div>
        <div style="grid-column:1 / -1;"><button type="submit" class="admin-button">Save Email Settings</button></div>
    </form>
</div>

