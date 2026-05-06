<?php
$formData = is_array($formData ?? null) ? $formData : [];
$linkOptions = is_array($linkOptions ?? null) ? $linkOptions : [];
require_once BASE_PATH . '/views/components/admin-selection-controls.php';
require_once BASE_PATH . '/views/components/admin-structured-items.php';
?>

<div class="admin-form-shell">
    <p class="admin-kicker">Website</p>
    <h2 class="admin-title">Footer Content</h2>
    <p class="admin-subtitle">Edit the footer columns and bottom legal/support line. Social links come from Site Settings.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="admin-card admin-form-section admin-form-section--status" style="padding:1rem;">
        <p class="admin-note" style="margin:0;">Use the row editors below instead of typing parser formats. Add, remove, and reorder footer rows directly.</p>
    </div>

    <form method="post" action="/admin/footer-seo" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>

        <details class="admin-card admin-form-section is-collapsible" style="grid-column:1 / -1;padding:1.1rem;" open>
            <summary class="admin-card__summary">
                <span class="admin-card__summary-title">About Column</span>
                <span class="admin-card__summary-note"><?php echo htmlspecialchars((string) count(admin_structured_parse_items((string) ($formData['about_items_text'] ?? ''), false, false)), ENT_QUOTES, 'UTF-8'); ?> saved items</span>
            </summary>
            <p class="admin-kicker">About Column</p>
            <div class="admin-grid cols-2" style="margin-top:1rem;">
                <div class="admin-field">
                    <label for="about_heading">Heading</label>
                    <input id="about_heading" name="about_heading" type="text" value="<?php echo htmlspecialchars((string) ($formData['about_heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <?php
                admin_structured_render_items_editor([
                    'id' => 'about_items_text',
                    'name' => 'about_items_text',
                    'label' => 'About Chip Items',
                    'value' => (string) ($formData['about_items_text'] ?? ''),
                    'allow_body' => false,
                    'allow_url' => false,
                    'add_label' => 'Add Chip Item',
                    'note' => 'These items are short chips without links.',
                ]);
                ?>
            </div>
            <div class="admin-field" style="margin-bottom:0;">
                <label for="about_body_text">Body Text</label>
                <textarea id="about_body_text" name="about_body_text" rows="4"><?php echo htmlspecialchars((string) ($formData['about_body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </details>

        <details class="admin-card admin-form-section is-collapsible" style="padding:1.1rem;" open>
            <summary class="admin-card__summary">
                <span class="admin-card__summary-title">Shop Column</span>
                <span class="admin-card__summary-note"><?php echo htmlspecialchars((string) count(admin_structured_parse_items((string) ($formData['shop_items_text'] ?? ''), false, true)), ENT_QUOTES, 'UTF-8'); ?> saved links</span>
            </summary>
            <p class="admin-kicker">Shop Column</p>
            <div class="admin-field">
                <label for="shop_heading">Heading</label>
                <input id="shop_heading" name="shop_heading" type="text" value="<?php echo htmlspecialchars((string) ($formData['shop_heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <?php
            admin_structured_render_items_editor([
                'id' => 'shop_items_text',
                'name' => 'shop_items_text',
                'label' => 'Shop Links',
                'value' => (string) ($formData['shop_items_text'] ?? ''),
                'allow_body' => false,
                'allow_url' => true,
                'link_options' => $linkOptions,
                'add_label' => 'Add Shop Link',
                'note' => 'Choose existing pages first. Use custom URL only when needed.',
            ]);
            ?>
        </details>

        <details class="admin-card admin-form-section is-collapsible" style="padding:1.1rem;" open>
            <summary class="admin-card__summary">
                <span class="admin-card__summary-title">Service Column</span>
                <span class="admin-card__summary-note"><?php echo htmlspecialchars((string) count(admin_structured_parse_items((string) ($formData['service_items_text'] ?? ''), false, true)), ENT_QUOTES, 'UTF-8'); ?> saved links</span>
            </summary>
            <p class="admin-kicker">Service Column</p>
            <div class="admin-field">
                <label for="service_heading">Heading</label>
                <input id="service_heading" name="service_heading" type="text" value="<?php echo htmlspecialchars((string) ($formData['service_heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <?php
            admin_structured_render_items_editor([
                'id' => 'service_items_text',
                'name' => 'service_items_text',
                'label' => 'Service Links',
                'value' => (string) ($formData['service_items_text'] ?? ''),
                'allow_body' => false,
                'allow_url' => true,
                'link_options' => $linkOptions,
                'add_label' => 'Add Service Link',
                'note' => 'Use these for contact, order status, and service related links.',
            ]);
            ?>
        </details>

        <details class="admin-card admin-form-section is-collapsible" style="padding:1.1rem;" open>
            <summary class="admin-card__summary">
                <span class="admin-card__summary-title">Business Column</span>
                <span class="admin-card__summary-note"><?php echo htmlspecialchars((string) count(admin_structured_parse_items((string) ($formData['business_items_text'] ?? ''), false, true)), ENT_QUOTES, 'UTF-8'); ?> saved links</span>
            </summary>
            <p class="admin-kicker">Business Column</p>
            <div class="admin-field">
                <label for="business_heading">Heading</label>
                <input id="business_heading" name="business_heading" type="text" value="<?php echo htmlspecialchars((string) ($formData['business_heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <?php
            admin_structured_render_items_editor([
                'id' => 'business_items_text',
                'name' => 'business_items_text',
                'label' => 'Business Links Or Notes',
                'value' => (string) ($formData['business_items_text'] ?? ''),
                'allow_body' => false,
                'allow_url' => true,
                'link_options' => $linkOptions,
                'add_label' => 'Add Business Item',
                'note' => 'Leave link blank for plain text items.',
            ]);
            ?>
        </details>

        <div class="admin-card" style="grid-column:1 / -1;padding:1.1rem;">
            <p class="admin-kicker">Footer Bottom</p>
            <div class="admin-field" style="margin-bottom:0;">
                <label for="bottom_body_text">Bottom Copy</label>
                <textarea id="bottom_body_text" name="bottom_body_text" rows="3"><?php echo htmlspecialchars((string) ($formData['bottom_body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div style="grid-column:1 / -1;">
            <button type="submit" class="admin-button">Save Footer Content</button>
        </div>
    </form>
</div>
