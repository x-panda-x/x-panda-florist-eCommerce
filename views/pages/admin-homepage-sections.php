<?php
$formData = is_array($formData ?? null) ? $formData : [];
$assets = is_array($assets ?? null) ? $assets : [];
$currentHeroAsset = is_array($currentHeroAsset ?? null) ? $currentHeroAsset : null;
$heroAssets = is_array($heroAssets ?? null) ? $heroAssets : [];
$homepageProductSections = is_array($homepageProductSections ?? null) ? $homepageProductSections : [];
$homepageProductOptions = is_array($homepageProductOptions ?? null) ? $homepageProductOptions : [];
$homepageSectionPresetOptions = is_array($homepageSectionPresetOptions ?? null) ? $homepageSectionPresetOptions : [];
$linkOptions = is_array($linkOptions ?? null) ? $linkOptions : [];
require_once BASE_PATH . '/views/components/admin-selection-controls.php';
require_once BASE_PATH . '/views/components/admin-structured-items.php';
$sections = [
    'hero' => [
        'label' => 'Hero',
        'item_help' => 'Add secondary action buttons for the hero.',
        'allow_body' => false,
        'allow_url' => true,
        'add_label' => 'Add Hero Action',
    ],
    'quick_links' => [
        'label' => 'Quick Links',
        'item_help' => 'Add shortcut cards with optional description and link.',
        'allow_body' => true,
        'allow_url' => true,
        'add_label' => 'Add Quick Link Card',
    ],
    'feature_intro' => [
        'label' => 'Feature Intro',
        'item_help' => 'Add supporting notes shown near the feature intro.',
        'allow_body' => true,
        'allow_url' => false,
        'add_label' => 'Add Intro Note',
    ],
    'features' => [
        'label' => 'Feature Cards',
        'item_help' => 'Add homepage feature cards.',
        'allow_body' => true,
        'allow_url' => false,
        'add_label' => 'Add Feature Card',
    ],
    'trust' => [
        'label' => 'Trust',
        'item_help' => 'Add trust points shown on the homepage.',
        'allow_body' => true,
        'allow_url' => false,
        'add_label' => 'Add Trust Point',
    ],
    'newsletter' => [
        'label' => 'Newsletter',
        'item_help' => 'Add optional newsletter helper lines.',
        'allow_body' => true,
        'allow_url' => false,
        'add_label' => 'Add Newsletter Note',
    ],
    'seo' => [
        'label' => 'SEO',
        'item_help' => 'Add SEO-supporting lines shown in the homepage content block.',
        'allow_body' => true,
        'allow_url' => false,
        'add_label' => 'Add SEO Line',
    ],
];

$sectionProductSortMap = static function (array $section): array {
    $map = [];

    foreach (($section['products'] ?? []) as $product) {
        if (!is_array($product)) {
            continue;
        }

        $productId = (int) ($product['id'] ?? 0);

        if ($productId > 0) {
            $map[$productId] = (int) ($product['section_sort_order'] ?? 0);
        }
    }

    return $map;
};

$homepageProductOptionMap = [];

foreach ($homepageProductOptions as $homepageProductOption) {
    $optionId = (int) ($homepageProductOption['id'] ?? 0);

    if ($optionId <= 0) {
        continue;
    }

    $homepageProductOptionMap[$optionId] = $homepageProductOption;
}

$renderHomepageProductPicker = static function (string $fieldPrefix, string $inputIdPrefix, array $selectedSortMap) use ($homepageProductOptionMap): void {
    if ($homepageProductOptionMap === []) {
        echo '<div class="admin-empty-state">Add products first, then return here to assign them to homepage sections.</div>';
        return;
    }

    asort($selectedSortMap);

    echo '<div class="homepage-products-editor" data-homepage-products-editor data-field-prefix="' . admin_selection_h($fieldPrefix) . '">';
    echo '<div class="homepage-products-editor__top">';
    echo '<div class="admin-note">Selected products show on the homepage slider in the order listed below.</div>';
    echo '<div class="homepage-products-editor__add">';
    echo '<label for="' . admin_selection_h($inputIdPrefix . '_add_product') . '">Add Product</label>';
    echo '<div class="homepage-products-editor__add-row">';
    echo '<select id="' . admin_selection_h($inputIdPrefix . '_add_product') . '" data-homepage-add-product>';
    echo '<option value="">Choose a product</option>';

    foreach ($homepageProductOptionMap as $productId => $product) {
        $productName = (string) ($product['name'] ?? 'Product');
        $productSlug = (string) ($product['slug'] ?? '');
        $productPrice = number_format((float) ($product['display_price'] ?? 0), 2);
        $productLabel = $productName . ' · ' . $productSlug . ' · $' . $productPrice;
        echo '<option value="' . admin_selection_h((string) $productId) . '" data-product-name="' . admin_selection_h($productName) . '" data-product-meta="' . admin_selection_h($productLabel) . '">' . admin_selection_h($productLabel) . '</option>';
    }

    echo '</select>';
    echo '<button type="button" class="admin-button-secondary" data-homepage-add-product-button>Add</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="homepage-products-editor__list" data-homepage-selected-products>';

    if ($selectedSortMap !== []) {
        foreach ($selectedSortMap as $productId => $sortValue) {
            $product = $homepageProductOptionMap[(int) $productId] ?? null;

            if (!is_array($product)) {
                continue;
            }

            $productName = (string) ($product['name'] ?? 'Product');
            $productSlug = (string) ($product['slug'] ?? '');
            $productPrice = number_format((float) ($product['display_price'] ?? 0), 2);
            ?>
            <div class="homepage-products-editor__item" data-homepage-selected-item data-product-id="<?php echo admin_selection_h((string) $productId); ?>">
                <div class="homepage-products-editor__item-main">
                    <strong><?php echo admin_selection_h($productName); ?></strong>
                    <small><?php echo admin_selection_h($productSlug); ?> · $<?php echo admin_selection_h($productPrice); ?></small>
                </div>
                <div class="homepage-products-editor__item-actions">
                    <span class="admin-status-pill is-confirmed" data-homepage-product-position>1</span>
                    <button type="button" class="admin-text-button" data-homepage-product-move-up>Up</button>
                    <button type="button" class="admin-text-button" data-homepage-product-move-down>Down</button>
                    <button type="button" class="admin-text-button" data-homepage-product-remove style="color:#8b3c39;">Remove</button>
                </div>
                <input type="hidden" name="<?php echo admin_selection_h($fieldPrefix); ?>[product_ids][]" value="<?php echo admin_selection_h((string) $productId); ?>" data-homepage-product-id-input>
                <input type="hidden" name="<?php echo admin_selection_h($fieldPrefix); ?>[product_sort_orders][<?php echo admin_selection_h((string) $productId); ?>]" value="<?php echo admin_selection_h((string) max(0, (int) $sortValue)); ?>" data-homepage-product-order-input>
            </div>
            <?php
        }
    } else {
        echo '<div class="admin-empty-state" data-homepage-empty-products>Select products to show this slider on the homepage.</div>';
    }

    echo '</div>';
    ?>
    <template data-homepage-product-template>
        <div class="homepage-products-editor__item" data-homepage-selected-item data-product-id="">
            <div class="homepage-products-editor__item-main">
                <strong data-homepage-product-name></strong>
                <small data-homepage-product-meta></small>
            </div>
            <div class="homepage-products-editor__item-actions">
                <span class="admin-status-pill is-confirmed" data-homepage-product-position>1</span>
                <button type="button" class="admin-text-button" data-homepage-product-move-up>Up</button>
                <button type="button" class="admin-text-button" data-homepage-product-move-down>Down</button>
                <button type="button" class="admin-text-button" data-homepage-product-remove style="color:#8b3c39;">Remove</button>
            </div>
            <input type="hidden" value="" data-homepage-product-id-input>
            <input type="hidden" value="10" data-homepage-product-order-input>
        </div>
    </template>
    <?php
    echo '</div>';
};
?>

<div class="admin-form-shell">
    <p class="admin-kicker">Website</p>
    <h2 class="admin-title">Homepage Manager</h2>
    <p class="admin-subtitle">Use this page to control the hero, homepage product sliders, and other homepage content blocks.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <nav class="admin-section-nav" aria-label="Homepage manager sections">
        <ul class="admin-section-nav__list">
            <li><a href="#homepage-hero-media">Hero Image</a></li>
            <li><a href="#homepage-product-sections">Product Sliders</a></li>
            <li><a href="#homepage-content-sections">Content Blocks</a></li>
        </ul>
    </nav>

    <div id="homepage-hero-media" class="admin-card admin-form-section admin-form-section--website">
        <p class="admin-kicker">Hero Image</p>
        <h3 class="orders-console-heading" style="margin-bottom:0.35rem;">Current homepage hero image</h3>
        <p class="admin-subtitle" style="margin-bottom:1rem;">Upload a new hero and activate it immediately, or choose an existing hero image below. Recommended size: wide landscape image at roughly 1800px+ width.</p>

        <?php if ($currentHeroAsset !== null && !empty($currentHeroAsset['public_path'])): ?>
            <div class="admin-card admin-soft-card" style="padding:1rem;margin-bottom:1.25rem;">
                <div class="admin-grid cols-2" style="align-items:start;">
                    <div>
                        <img src="<?php echo htmlspecialchars((string) ($currentHeroAsset['public_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="Current homepage hero" style="width:100%;max-width:540px;aspect-ratio:16/9;object-fit:cover;border-radius:18px;border:1px solid var(--admin-border);">
                    </div>
                    <div>
                        <span class="admin-status-pill is-confirmed">Currently Active</span>
                        <p class="admin-note" style="margin-top:0.85rem;"><strong>File:</strong> <?php echo htmlspecialchars((string) ($currentHeroAsset['original_name'] ?? $currentHeroAsset['file_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="admin-note"><strong>Asset ID:</strong> <?php echo htmlspecialchars((string) ($currentHeroAsset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="admin-note"><strong>Collection:</strong> <?php echo htmlspecialchars((string) ($currentHeroAsset['collection_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="admin-note"><strong>Dimensions:</strong> <?php echo htmlspecialchars((string) (($currentHeroAsset['width'] ?? '?') . ' x ' . ($currentHeroAsset['height'] ?? '?')), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-empty-state" style="margin-bottom:1.25rem;">
                No homepage hero image is currently active. Upload one below to make the storefront hero explicit and reliable.
            </div>
        <?php endif; ?>

        <div class="admin-card admin-soft-card" style="padding:1.1rem;margin-bottom:1.25rem;">
            <p class="admin-kicker">Upload New Hero Image</p>
            <form method="post" action="/admin/homepage/hero/upload" enctype="multipart/form-data" class="admin-grid cols-2" style="margin-top:0.85rem;">
                <?php echo csrf_field(); ?>
                <div class="admin-field">
                    <label for="hero_image">Hero Image File</label>
                    <input id="hero_image" name="hero_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                </div>
                <div class="admin-field">
                    <label for="hero_alt_text">Alt Text</label>
                    <input id="hero_alt_text" name="hero_alt_text" type="text" value="Homepage hero image" placeholder="Homepage hero image">
                </div>
                <div class="admin-form-actions" style="grid-column:1 / -1;">
                    <button type="submit" class="admin-button">Upload And Make Active</button>
                </div>
            </form>
        </div>

        <div class="admin-card admin-soft-card" style="padding:1.1rem;">
            <p class="admin-kicker">Existing Hero Images</p>
            <?php if ($heroAssets === []): ?>
                <div class="admin-empty-state" style="margin-top:0.85rem;">
                    No homepage hero images are stored yet.
                </div>
            <?php else: ?>
                <div class="admin-grid cols-3 admin-choice-grid" style="margin-top:0.85rem;">
                    <?php foreach ($heroAssets as $asset): ?>
                        <?php $isActiveHero = (int) ($asset['id'] ?? 0) === (int) ($currentHeroAsset['id'] ?? 0); ?>
                        <div class="admin-soft-card admin-card admin-choice-card" style="padding:1rem;">
                            <img src="<?php echo htmlspecialchars((string) ($asset['public_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($asset['alt_text'] ?? 'Homepage hero asset'), ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:16px;border:1px solid var(--admin-border);">
                            <div style="margin-top:0.9rem;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
                                    <strong><?php echo htmlspecialchars((string) ($asset['original_name'] ?? $asset['file_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if ($isActiveHero): ?>
                                        <span class="admin-status-pill is-confirmed">Active Hero</span>
                                    <?php endif; ?>
                                </div>
                                <div class="admin-note" style="margin-top:0.45rem;">ID <?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) (($asset['width'] ?? '?') . ' x ' . ($asset['height'] ?? '?')), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="admin-form-actions" style="margin-top:0.9rem;">
                                <?php if (!$isActiveHero): ?>
                                    <form method="post" action="/admin/homepage/hero/activate">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars((string) ($asset['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="admin-button-secondary">Make Active</button>
                                    </form>
                                    <form method="post" action="/admin/homepage/hero/delete" onsubmit="return confirm('Delete this unused hero image?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars((string) ($asset['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="admin-text-button" style="color:#8b3c39;">Delete Unused Asset</button>
                                    </form>
                                <?php else: ?>
                                    <span class="admin-note">Activate another hero first to remove this image.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="homepage-product-sections" class="admin-card admin-form-section admin-form-section--content">
        <p class="admin-kicker">Homepage Product Sections</p>
        <h3 class="orders-console-heading" style="margin-bottom:0.35rem;">Occasion and collection sliders</h3>
        <p class="admin-subtitle" style="margin-bottom:1rem;">Simple workflow: choose visible sliders, move them up/down, choose products, move products up/down, then save.</p>

        <?php if ($homepageProductSections !== []): ?>
            <div class="admin-current-list" style="margin-bottom:1rem;">
                <div><strong>Current live homepage sliders:</strong></div>
                <?php foreach ($homepageProductSections as $homepageSection): ?>
                    <div>
                        <?php echo htmlspecialchars((string) ($homepageSection['sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> ·
                        <?php echo htmlspecialchars((string) ($homepageSection['title'] ?? 'Homepage Section'), ENT_QUOTES, 'UTF-8'); ?> ·
                        <?php echo !empty($homepageSection['is_active']) ? 'Visible' : 'Hidden'; ?> ·
                        <?php echo htmlspecialchars((string) count((array) ($homepageSection['products'] ?? [])), ENT_QUOTES, 'UTF-8'); ?> products
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/homepage/product-sections" class="admin-grid" data-homepage-sections-form>
            <?php echo csrf_field(); ?>
            <div class="admin-empty-state" style="border-color:#bfdbfe;background:#eff6ff;color:#1e3a8a;">
                Save rule: only <strong>Save Product Sliders</strong> applies section visibility, section order, product assignment, and product order.
            </div>

            <?php if ($homepageProductSections === []): ?>
                <div class="admin-empty-state">
                    No homepage product sections are configured yet. Add a section below.
                </div>
            <?php endif; ?>

            <?php foreach ($homepageProductSections as $sectionIndex => $homepageSection): ?>
                <?php
                $sectionId = (int) ($homepageSection['id'] ?? 0);
                $fieldPrefix = 'sections[' . $sectionId . ']';
                $inputIdPrefix = 'homepage_section_' . $sectionId;
                $selectedSortMap = $sectionProductSortMap($homepageSection);
                ?>
                <details class="admin-card admin-soft-card homepage-section-card" data-homepage-section-card <?php echo $sectionIndex < 2 ? 'open' : ''; ?>>
                    <summary class="homepage-section-card__summary">
                        <span>
                            <?php echo htmlspecialchars((string) ($homepageSection['title'] ?? 'Homepage Section'), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="admin-status-pill <?php echo !empty($homepageSection['is_active']) ? 'is-confirmed' : 'is-cancelled'; ?>">
                            <?php echo !empty($homepageSection['is_active']) ? 'Visible' : 'Hidden'; ?> · <?php echo htmlspecialchars((string) count($selectedSortMap), ENT_QUOTES, 'UTF-8'); ?> products
                        </span>
                    </summary>

                    <input type="hidden" name="<?php echo htmlspecialchars($fieldPrefix, ENT_QUOTES, 'UTF-8'); ?>[id]" value="<?php echo htmlspecialchars((string) $sectionId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        id="<?php echo htmlspecialchars($inputIdPrefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>"
                        name="<?php echo htmlspecialchars($fieldPrefix, ENT_QUOTES, 'UTF-8'); ?>[sort_order]"
                        type="hidden"
                        value="<?php echo htmlspecialchars((string) ($homepageSection['sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                        data-homepage-section-order-input
                    >

                    <div class="admin-grid cols-2 homepage-section-card__fields">
                        <div class="admin-field">
                            <label for="<?php echo htmlspecialchars($inputIdPrefix . '_title', ENT_QUOTES, 'UTF-8'); ?>">Section Title On Homepage</label>
                            <input id="<?php echo htmlspecialchars($inputIdPrefix . '_title', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($fieldPrefix, ENT_QUOTES, 'UTF-8'); ?>[title]" type="text" value="<?php echo htmlspecialchars((string) ($homepageSection['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="admin-field">
                            <label>Section Order On Homepage</label>
                            <div class="homepage-order-controls">
                                <span class="admin-status-pill is-confirmed" data-homepage-section-position>1</span>
                                <button type="button" class="admin-text-button" data-homepage-section-move-up>Move Up</button>
                                <button type="button" class="admin-text-button" data-homepage-section-move-down>Move Down</button>
                            </div>
                        </div>
                    </div>

                    <div class="admin-field">
                        <label class="admin-checkbox" style="margin-top:0.25rem;">
                            <input name="<?php echo htmlspecialchars($fieldPrefix, ENT_QUOTES, 'UTF-8'); ?>[is_active]" type="checkbox" value="1" <?php echo !empty($homepageSection['is_active']) ? 'checked' : ''; ?>>
                            <span>Show this section on homepage</span>
                        </label>
                    </div>

                    <div class="admin-field" style="margin-bottom:0;">
                        <label>Products In This Section</label>
                        <?php $renderHomepageProductPicker($fieldPrefix, $inputIdPrefix, $selectedSortMap); ?>
                    </div>

                    <details class="admin-soft-card" style="margin-top:1rem;padding:0.85rem;">
                        <summary class="admin-note" style="cursor:pointer;font-weight:600;">Optional: Small label and section link</summary>
                        <div class="admin-grid cols-3" style="margin-top:0.85rem;">
                            <div class="admin-field">
                                <label for="<?php echo htmlspecialchars($inputIdPrefix . '_subheading', ENT_QUOTES, 'UTF-8'); ?>">Small Label Above Title</label>
                                <input id="<?php echo htmlspecialchars($inputIdPrefix . '_subheading', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($fieldPrefix, ENT_QUOTES, 'UTF-8'); ?>[subheading]" type="text" value="<?php echo htmlspecialchars((string) ($homepageSection['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="admin-field">
                                <label for="<?php echo htmlspecialchars($inputIdPrefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>">Section Link Label</label>
                                <input id="<?php echo htmlspecialchars($inputIdPrefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($fieldPrefix, ENT_QUOTES, 'UTF-8'); ?>[cta_label]" type="text" value="<?php echo htmlspecialchars((string) ($homepageSection['cta_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <?php
                            admin_selection_render_hybrid_field([
                                'id' => $inputIdPrefix . '_cta_url',
                                'name' => $fieldPrefix . '[cta_url]',
                                'label' => 'Section Link Destination',
                                'value' => (string) ($homepageSection['cta_url'] ?? ''),
                                'options' => $linkOptions,
                                'empty_label' => 'No section link',
                                'custom_label' => 'Custom URL',
                                'custom_input_label' => 'Custom Link URL',
                                'placeholder' => '/occasions',
                            ]);
                            ?>
                        </div>
                    </details>
                </details>
            <?php endforeach; ?>

            <details class="admin-card admin-soft-card homepage-section-card">
                <summary class="homepage-section-card__summary">
                    <span>Add New Slider Section (Optional)</span>
                    <span class="admin-status-pill">Collapsed</span>
                </summary>
                <p class="admin-kicker">Add Section</p>
                <div class="admin-field">
                    <label for="new_homepage_section_preset">Start From Existing Collection</label>
                    <select id="new_homepage_section_preset" data-homepage-section-preset>
                        <option value="">Choose an occasion, category, or collection</option>
                        <?php admin_selection_render_option_groups($homepageSectionPresetOptions, ''); ?>
                    </select>
                    <small class="admin-note">Choosing a preset fills the title, link, and product choices below. Use the fields manually only for a custom section.</small>
                </div>
                <div class="admin-grid cols-3">
                    <div class="admin-field">
                        <label for="new_homepage_section_title">Section Title On Homepage</label>
                        <input id="new_homepage_section_title" name="new_section[title]" type="text" value="">
                    </div>
                    <div class="admin-field">
                        <label for="new_homepage_section_subheading">Small Label Above Title</label>
                        <input id="new_homepage_section_subheading" name="new_section[subheading]" type="text" value="Collection">
                    </div>
                    <div class="admin-field">
                        <label for="new_homepage_section_sort_order">Section Order On Homepage</label>
                        <input id="new_homepage_section_sort_order" name="new_section[sort_order]" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ((count($homepageProductSections) + 1) * 10), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="admin-grid cols-3">
                    <div class="admin-field">
                        <label for="new_homepage_section_cta_label">Link Label</label>
                        <input id="new_homepage_section_cta_label" name="new_section[cta_label]" type="text" value="">
                    </div>
                    <?php
                    admin_selection_render_hybrid_field([
                        'id' => 'new_homepage_section_cta_url',
                        'name' => 'new_section[cta_url]',
                        'label' => 'Section Link Destination',
                        'value' => '',
                        'options' => $linkOptions,
                        'empty_label' => 'No section link',
                        'custom_label' => 'Custom URL',
                        'custom_input_label' => 'Custom Link URL',
                        'placeholder' => '/best-sellers',
                    ]);
                    ?>
                    <div class="admin-field">
                        <label class="admin-checkbox" style="margin-top:1.9rem;">
                            <input name="new_section[is_active]" type="checkbox" value="1" checked>
                            <span>Show this section on homepage</span>
                        </label>
                    </div>
                </div>
                <div class="admin-field" style="margin-bottom:0;">
                    <label>Choose Products To Show In This Section</label>
                    <?php $renderHomepageProductPicker('new_section', 'new_homepage_section', []); ?>
                </div>
            </details>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button" data-homepage-slider-save-button>Save Product Sliders</button>
            </div>
        </form>
    </div>

    <details class="admin-card admin-form-section is-collapsible" style="margin-top:1rem;padding:1.1rem;">
        <summary class="admin-card__summary">
            <span class="admin-card__summary-title">Advanced Homepage Content Blocks (Not Slider Settings)</span>
            <span class="admin-card__summary-note">Optional</span>
        </summary>
        <p class="admin-note" style="margin:0 0 1rem;">Use this area only for homepage text/media content blocks. It does not save product slider section changes.</p>

        <form id="homepage-content-sections" method="post" action="/admin/homepage" class="admin-grid">
            <?php echo csrf_field(); ?>

            <?php foreach ($sections as $prefix => $section): ?>
            <?php $itemsValue = (string) ($formData[$prefix . '_items_text'] ?? ''); ?>
            <?php $itemsCount = count(admin_structured_parse_items($itemsValue, !empty($section['allow_body']), !empty($section['allow_url']))); ?>
            <details class="admin-card admin-form-section is-collapsible" style="padding:1.1rem;" <?php echo in_array($prefix, ['hero', 'quick_links'], true) ? 'open' : ''; ?>>
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
                        <label for="<?php echo htmlspecialchars($prefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>">Primary CTA Label</label>
                        <input id="<?php echo htmlspecialchars($prefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_cta_label', ENT_QUOTES, 'UTF-8'); ?>" type="text" value="<?php echo htmlspecialchars((string) ($formData[$prefix . '_cta_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <?php
                    admin_selection_render_hybrid_field([
                        'id' => $prefix . '_cta_url',
                        'name' => $prefix . '_cta_url',
                        'label' => 'Primary CTA Destination',
                        'value' => (string) ($formData[$prefix . '_cta_url'] ?? ''),
                        'options' => $linkOptions,
                        'empty_label' => 'No CTA link',
                        'custom_label' => 'Custom URL',
                        'custom_input_label' => 'Custom CTA URL',
                        'placeholder' => '/best-sellers',
                    ]);
                    ?>
                </div>

                <div class="admin-grid cols-3">
                    <?php if ($prefix === 'hero'): ?>
                        <input type="hidden" name="hero_media_asset_id" value="<?php echo htmlspecialchars((string) ($formData['hero_media_asset_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="admin-field">
                            <label>Hero Image</label>
                            <div class="admin-empty-state">Use the dedicated hero image manager above to upload, activate, or delete homepage hero assets safely.</div>
                        </div>
                    <?php else: ?>
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
                    <?php endif; ?>
                    <div class="admin-field">
                        <label for="<?php echo htmlspecialchars($prefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>">Sort Order</label>
                        <input id="<?php echo htmlspecialchars($prefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($prefix . '_sort_order', ENT_QUOTES, 'UTF-8'); ?>" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($formData[$prefix . '_sort_order'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="admin-field">
                        <label class="admin-checkbox" style="margin-top:1.9rem;">
                            <input name="<?php echo htmlspecialchars($prefix . '_is_enabled', ENT_QUOTES, 'UTF-8'); ?>" type="checkbox" value="1" <?php echo !empty($formData[$prefix . '_is_enabled']) ? 'checked' : ''; ?>>
                            <span>Show This Block On Homepage</span>
                        </label>
                    </div>
                </div>

                <?php
                admin_structured_render_items_editor([
                    'id' => $prefix . '_items_text',
                    'name' => $prefix . '_items_text',
                    'label' => 'Block Items',
                    'value' => $itemsValue,
                    'allow_body' => !empty($section['allow_body']),
                    'allow_url' => !empty($section['allow_url']),
                    'link_options' => $linkOptions,
                    'add_label' => (string) ($section['add_label'] ?? 'Add Item'),
                    'note' => (string) ($section['item_help'] ?? ''),
                ]);
                ?>
            </details>
            <?php endforeach; ?>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button-secondary">Save Advanced Homepage Content</button>
            </div>
        </form>
    </details>
</div>
