<?php
$formData = is_array($formData ?? null) ? $formData : [];
$assets = is_array($assets ?? null) ? $assets : [];
$linkOptions = is_array($linkOptions ?? null) ? $linkOptions : [];
require_once BASE_PATH . '/views/components/admin-selection-controls.php';
require_once BASE_PATH . '/views/components/admin-structured-items.php';
$sections = [
    'contact_hero' => ['label' => 'Contact Hero', 'item_help' => 'Add optional helper lines for the contact hero.', 'add_label' => 'Add Contact Hero Item'],
    'contact_support' => ['label' => 'Contact Support', 'item_help' => 'Add support notes shown on the contact page.', 'add_label' => 'Add Support Note'],
    'order_status_intro' => ['label' => 'Order Status Intro', 'item_help' => 'Add guidance and lookup tips for order status.', 'add_label' => 'Add Order Status Tip'],
    'order_status_empty' => ['label' => 'Order Status Empty', 'item_help' => 'Optional text shown when no order is found.', 'add_label' => 'Add Empty-State Tip'],
    'checkout_help' => ['label' => 'Checkout Help', 'item_help' => 'Add checkout policy/help lines.', 'add_label' => 'Add Checkout Help Line'],
    'payment_help' => ['label' => 'Payment Help', 'item_help' => 'Add payment-stage helper notes.', 'add_label' => 'Add Payment Help Line'],
    'order_confirmation_help' => ['label' => 'Order Confirmation Help', 'item_help' => 'Add next-step actions for confirmation page.', 'add_label' => 'Add Confirmation Action'],
    'best_sellers_intro' => ['label' => 'Best Sellers Intro', 'item_help' => 'Add helper lines/cards for the best sellers page.', 'add_label' => 'Add Best Sellers Item'],
    'occasions_intro' => ['label' => 'Occasions Intro', 'item_help' => 'Add helper lines for occasions page.', 'add_label' => 'Add Occasions Item'],
    'search_intro' => ['label' => 'Search Intro', 'item_help' => 'Add optional search intro helper lines.', 'add_label' => 'Add Search Intro Item'],
    'search_empty' => ['label' => 'Search Empty', 'item_help' => 'Add empty-result helper lines.', 'add_label' => 'Add Empty Search Item'],
    'product_detail_helper' => ['label' => 'Product Detail Helper', 'item_help' => 'Add helper notes near the product detail area.', 'add_label' => 'Add Product Helper Item'],
    'product_detail_related' => ['label' => 'Product Detail Related', 'item_help' => 'Add notes for related products module.', 'add_label' => 'Add Related Item'],
];
?>

<div class="admin-form-shell">
    <p class="admin-kicker">Website</p>
    <h2 class="admin-title">Public Page Content</h2>
    <p class="admin-subtitle">Edit text and media for support pages and helper sections without changing storefront logic.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <nav class="admin-section-nav" aria-label="Public page sections">
        <ul class="admin-section-nav__list">
            <li><a href="#public-pages-contact">Page Blocks</a></li>
        </ul>
    </nav>

    <div class="admin-card admin-form-section admin-form-section--status" style="padding:1rem;">
        <p class="admin-note" style="margin:0;">Tip: each section below has the same save behavior. Values already shown in each field are the currently saved values.</p>
    </div>

    <form id="public-pages-contact" method="post" action="/admin/public-pages" class="admin-grid" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>

        <?php foreach ($sections as $prefix => $section): ?>
            <?php $itemsValue = (string) ($formData[$prefix . '_items_text'] ?? ''); ?>
            <?php $itemsCount = count(admin_structured_parse_items($itemsValue, true, true)); ?>
            <details class="admin-card admin-form-section is-collapsible" style="padding:1.1rem;" <?php echo in_array($prefix, ['contact_hero', 'contact_support', 'order_status_intro'], true) ? 'open' : ''; ?>>
                <summary class="admin-card__summary">
                    <span class="admin-card__summary-title"><?php echo htmlspecialchars((string) $section['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="admin-card__summary-note"><?php echo htmlspecialchars((string) $itemsCount, ENT_QUOTES, 'UTF-8'); ?> saved items</span>
                </summary>
                <div class="admin-grid cols-2" style="margin-top:1rem;">
                    <div class="admin-field">
                        <label for="<?php echo htmlspecialchars($prefix . '_subheading', ENT_QUOTES, 'UTF-8'); ?>">Eyebrow / Subheading</label>
                        <input id="<?php echo htmlspecialchars($prefix . '_subheading', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_subheading', ENT_QUOTES, 'UTF-8'); ?>" type="text" value="<?php echo htmlspecialchars((string) ($formData[$prefix . '_subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="admin-field">
                        <label for="<?php echo htmlspecialchars($prefix . '_heading', ENT_QUOTES, 'UTF-8'); ?>">Heading</label>
                        <input id="<?php echo htmlspecialchars($prefix . '_heading', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_heading', ENT_QUOTES, 'UTF-8'); ?>" type="text" value="<?php echo htmlspecialchars((string) ($formData[$prefix . '_heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="admin-field">
                    <label for="<?php echo htmlspecialchars($prefix . '_body_text', ENT_QUOTES, 'UTF-8'); ?>">Body Text</label>
                    <textarea id="<?php echo htmlspecialchars($prefix . '_body_text', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_body_text', ENT_QUOTES, 'UTF-8'); ?>" rows="4"><?php echo htmlspecialchars((string) ($formData[$prefix . '_body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="admin-grid cols-2">
                    <div class="admin-field">
                        <label for="<?php echo htmlspecialchars($prefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>">CTA Label</label>
                        <input id="<?php echo htmlspecialchars($prefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>" type="text" value="<?php echo htmlspecialchars((string) ($formData[$prefix . '_cta_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <?php
                    admin_selection_render_hybrid_field([
                        'id' => $prefix . '_cta_url',
                        'name' => $prefix . '_cta_url',
                        'label' => 'CTA Destination',
                        'value' => (string) ($formData[$prefix . '_cta_url'] ?? ''),
                        'options' => $linkOptions,
                        'empty_label' => 'No CTA link',
                        'custom_label' => 'Custom URL',
                        'custom_input_label' => 'Custom CTA URL',
                        'placeholder' => '/contact',
                    ]);
                    ?>
                </div>

                <div class="admin-grid cols-3">
                    <div class="admin-field">
                        <label for="<?php echo htmlspecialchars($prefix . '_media_asset_id', ENT_QUOTES, 'UTF-8'); ?>">Media Asset</label>
                        <select id="<?php echo htmlspecialchars($prefix . '_media_asset_id', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_media_asset_id', ENT_QUOTES, 'UTF-8'); ?>">
                            <option value="0">None</option>
                            <?php foreach ($assets as $asset): ?>
                                <option value="<?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($formData[$prefix . '_media_asset_id'] ?? 0) === (int) ($asset['id'] ?? 0) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string) ($asset['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="<?php echo htmlspecialchars($prefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>">Sort Order</label>
                        <input id="<?php echo htmlspecialchars($prefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($formData[$prefix . '_sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="admin-field">
                        <label class="admin-checkbox" style="margin-top:1.9rem;">
                            <input name="<?php echo htmlspecialchars($prefix . '_is_enabled', ENT_QUOTES, 'UTF-8'); ?>" type="checkbox" value="1" <?php echo !empty($formData[$prefix . '_is_enabled']) ? 'checked' : ''; ?>>
                            <span>Show This Block</span>
                        </label>
                    </div>
                </div>

                <?php
                admin_structured_render_items_editor([
                    'id' => $prefix . '_items_text',
                    'name' => $prefix . '_items_text',
                    'label' => 'Repeatable Items',
                    'value' => $itemsValue,
                    'allow_body' => true,
                    'allow_url' => true,
                    'link_options' => $linkOptions,
                    'add_label' => (string) ($section['add_label'] ?? 'Add Item'),
                    'note' => (string) ($section['item_help'] ?? ''),
                ]);
                ?>
            </details>
        <?php endforeach; ?>

        <div>
            <button type="submit" class="admin-button">Save Public Page Content</button>
        </div>
    </form>
</div>
