<?php
$banner = is_array($banner ?? null) ? $banner : [];
$assets = is_array($assets ?? null) ? $assets : [];
$linkOptions = is_array($linkOptions ?? null) ? $linkOptions : [];
$pageKeyOptions = is_array($pageKeyOptions ?? null) ? $pageKeyOptions : [];
$bannerPlacementOptions = is_array($bannerPlacementOptions ?? null) ? $bannerPlacementOptions : [];
$startsAtValue = !empty($banner['starts_at']) ? str_replace(' ', 'T', substr((string) $banner['starts_at'], 0, 16)) : '';
$endsAtValue = !empty($banner['ends_at']) ? str_replace(' ', 'T', substr((string) $banner['ends_at'], 0, 16)) : '';
require_once BASE_PATH . '/views/components/admin-selection-controls.php';
?>

<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Banner'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Banner Details</h2>
    <p class="admin-subtitle">Set where this banner appears, when it runs, and what customers should click.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '/admin/banners'), ENT_QUOTES, 'UTF-8'); ?>" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($bannerId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="admin-field">
            <label for="banner_key">Banner Key</label>
            <input id="banner_key" name="banner_key" type="text" required value="<?php echo htmlspecialchars((string) ($banner['banner_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <?php
        admin_selection_render_hybrid_field([
            'id' => 'placement',
            'name' => 'placement',
            'label' => 'Where To Show This Banner',
            'value' => (string) ($banner['placement'] ?? 'promo_strip'),
            'options' => $bannerPlacementOptions,
            'empty_label' => 'Choose placement',
            'custom_label' => 'Custom placement',
            'custom_input_label' => 'Custom Placement Key',
            'placeholder' => 'promo_strip',
            'required' => true,
            'note' => 'Promo Strip is the active storefront banner placement. Use custom only for a template-supported placement.',
        ]);
        ?>

        <?php
        admin_selection_render_hybrid_field([
            'id' => 'page_key',
            'name' => 'page_key',
            'label' => 'Page To Show Banner On',
            'value' => (string) ($banner['page_key'] ?? 'global'),
            'options' => $pageKeyOptions,
            'empty_label' => 'Choose page',
            'custom_label' => 'Custom page key',
            'custom_input_label' => 'Custom Page Key',
            'placeholder' => 'global',
            'required' => true,
        ]);
        ?>

        <div class="admin-field">
            <label for="sort_order">Order Within This Placement</label>
            <input id="sort_order" name="sort_order" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($banner['sort_order'] ?? 10), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="title">Headline</label>
            <input id="title" name="title" type="text" value="<?php echo htmlspecialchars((string) ($banner['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="subtitle">Subheading</label>
            <input id="subtitle" name="subtitle" type="text" value="<?php echo htmlspecialchars((string) ($banner['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="body_text">Message Text</label>
            <textarea id="body_text" name="body_text" rows="4"><?php echo htmlspecialchars((string) ($banner['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="admin-field">
            <label for="cta_label">Button Label</label>
            <input id="cta_label" name="cta_label" type="text" value="<?php echo htmlspecialchars((string) ($banner['cta_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <?php
        admin_selection_render_hybrid_field([
            'id' => 'cta_url',
            'name' => 'cta_url',
            'label' => 'Button Destination',
            'value' => (string) ($banner['cta_url'] ?? ''),
            'options' => $linkOptions,
            'empty_label' => 'No CTA link',
            'custom_label' => 'Custom URL',
            'custom_input_label' => 'Custom CTA URL',
            'placeholder' => '/best-sellers',
        ]);
        ?>

        <div class="admin-field">
            <label for="media_asset_id">Banner Image</label>
            <select id="media_asset_id" name="media_asset_id">
                <option value="0">None</option>
                <?php foreach ($assets as $asset): ?>
                    <option value="<?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($banner['media_asset_id'] ?? 0) === (int) ($asset['id'] ?? 0) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string) ($asset['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="admin-field">
            <label for="starts_at">Start Date/Time</label>
            <input id="starts_at" name="starts_at" type="datetime-local" value="<?php echo htmlspecialchars($startsAtValue, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label for="ends_at">End Date/Time</label>
            <input id="ends_at" name="ends_at" type="datetime-local" value="<?php echo htmlspecialchars($endsAtValue, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label class="admin-checkbox">
                <input id="is_enabled" name="is_enabled" type="checkbox" value="1" <?php echo !empty($banner['is_enabled']) ? 'checked' : ''; ?>>
                <span>Enabled for storefront rendering</span>
            </label>
        </div>

        <div style="grid-column:1 / -1;display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update Banner' : 'Save Banner'; ?></button>
            <a href="/admin/banners" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>
