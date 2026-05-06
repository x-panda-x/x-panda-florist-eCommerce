<?php require_once BASE_PATH . '/views/components/admin-selection-controls.php'; ?>

<div class="admin-form-shell">
    <p class="admin-kicker"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Create Product'), ENT_QUOTES, 'UTF-8'); ?></p>
    <h2 class="admin-title">Product Editor</h2>
    <p class="admin-subtitle">Set basic details first, then categories, occasions, add-ons, related items, images, and sizes.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <nav class="admin-section-nav" aria-label="Product form sections">
        <ul class="admin-section-nav__list">
            <li><a href="#product-basics">Basics</a></li>
            <li><a href="#product-taxonomy">Categories & Occasions</a></li>
            <li><a href="#product-addons">Add-Ons</a></li>
            <li><a href="#product-images">Images</a></li>
            <li><a href="#product-variants">Sizes</a></li>
        </ul>
    </nav>

    <form method="post" action="<?php echo htmlspecialchars((string) ($formAction ?? '/admin/products'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" class="admin-grid cols-2 admin-product-form" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <?php if (($formMode ?? 'create') === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($productId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div id="product-basics" class="admin-field" style="grid-column:1 / -1;">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required value="<?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="slug">Slug</label>
            <input id="slug" name="slug" type="text" required value="<?php echo htmlspecialchars((string) ($product['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field" style="grid-column:1 / -1;">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="admin-field">
            <label for="base_price">Base Price</label>
            <input id="base_price" name="base_price" type="number" min="0" step="0.01" required value="<?php echo htmlspecialchars((string) ($product['base_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="admin-field">
            <label>&nbsp;</label>
            <label class="admin-checkbox">
                <input id="is_featured" name="is_featured" type="checkbox" value="1" <?php echo !empty($product['is_featured']) ? 'checked' : ''; ?>>
                <span>Featured product</span>
            </label>
        </div>

        <div id="product-taxonomy" class="admin-card admin-form-section" style="padding:1.1rem;">
            <p class="admin-kicker">Categories</p>
            <?php if (empty($categories)): ?>
                <p class="admin-note">No categories available yet.</p>
            <?php else: ?>
                <?php admin_selection_render_filter_input('product_category_choices', 'Search categories'); ?>
                <?php admin_selection_render_bulk_controls('product_category_choices'); ?>
                <div id="product_category_choices" data-admin-filter-list>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryId = (int) ($category['id'] ?? 0); ?>
                    <label class="admin-checkbox" style="margin-top:0.55rem;" data-admin-filter-item data-admin-filter-text="<?php echo htmlspecialchars(trim((string) ($category['name'] ?? '') . ' ' . (string) ($category['slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                        <input name="category_ids[]" type="checkbox" value="<?php echo htmlspecialchars((string) $categoryId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($categoryId, $selectedCategoryIds ?? [], true) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-card admin-form-section" style="padding:1.1rem;">
            <p class="admin-kicker">Occasions</p>
            <?php if (empty($occasions)): ?>
                <p class="admin-note">No occasions available yet.</p>
            <?php else: ?>
                <?php admin_selection_render_filter_input('product_occasion_choices', 'Search occasions'); ?>
                <?php admin_selection_render_bulk_controls('product_occasion_choices'); ?>
                <div id="product_occasion_choices" data-admin-filter-list>
                <?php foreach ($occasions as $occasion): ?>
                    <?php $occasionId = (int) ($occasion['id'] ?? 0); ?>
                    <label class="admin-checkbox" style="margin-top:0.55rem;" data-admin-filter-item data-admin-filter-text="<?php echo htmlspecialchars(trim((string) ($occasion['name'] ?? '') . ' ' . (string) ($occasion['slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                        <input name="occasion_ids[]" type="checkbox" value="<?php echo htmlspecialchars((string) $occasionId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($occasionId, $selectedOccasionIds ?? [], true) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars((string) ($occasion['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="product-addons" class="admin-card admin-form-section" style="grid-column:1 / -1;padding:1.1rem;">
            <p class="admin-kicker">Assigned Add-Ons</p>
            <p class="admin-note">Select the extras customers can choose for this arrangement on the storefront product page.</p>
            <?php if (empty($availableAddons)): ?>
                <p class="admin-note" style="margin-top:1rem;">No add-ons available yet. Create them in the Add-Ons section first.</p>
            <?php else: ?>
                <?php admin_selection_render_filter_input('product_addon_choices', 'Search add-ons'); ?>
                <?php admin_selection_render_bulk_controls('product_addon_choices'); ?>
                <div id="product_addon_choices" class="admin-grid cols-3 admin-choice-grid" data-admin-filter-list style="margin-top:1rem;">
                    <?php foreach ($availableAddons as $addon): ?>
                        <?php $addonId = (int) ($addon['id'] ?? 0); ?>
                        <label class="admin-soft-card admin-card admin-choice-card" style="padding:1rem;display:flex;gap:0.8rem;align-items:flex-start;" data-admin-filter-item data-admin-filter-text="<?php echo htmlspecialchars(trim((string) ($addon['name'] ?? '') . ' ' . (string) ($addon['slug'] ?? '') . ' ' . number_format((float) ($addon['price'] ?? 0), 2)), ENT_QUOTES, 'UTF-8'); ?>">
                            <input
                                name="addon_ids[]"
                                type="checkbox"
                                value="<?php echo htmlspecialchars((string) $addonId, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo in_array($addonId, $selectedAddonIds ?? [], true) ? 'checked' : ''; ?>
                                style="width:1rem;height:1rem;margin-top:0.25rem;"
                            >
                            <span>
                                <strong><?php echo htmlspecialchars((string) ($addon['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="admin-note" style="display:block;"><?php echo htmlspecialchars((string) ($addon['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="admin-note" style="display:block;">$<?php echo htmlspecialchars(number_format((float) ($addon['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?> <?php echo !empty($addon['is_active']) ? '• Active' : '• Inactive'; ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-card admin-form-section" style="grid-column:1 / -1;padding:1.1rem;">
            <p class="admin-kicker">Related Products</p>
            <p class="admin-note">Choose manual cross-sells to show on the product detail page. The current product is excluded automatically.</p>
            <?php if (empty($availableRelatedProducts)): ?>
                <p class="admin-note" style="margin-top:1rem;">Add another product before assigning related products.</p>
            <?php else: ?>
                <?php admin_selection_render_filter_input('related_product_choices', 'Search products'); ?>
                <?php admin_selection_render_bulk_controls('related_product_choices'); ?>
                <div id="related_product_choices" class="admin-grid cols-3 admin-choice-grid" data-admin-filter-list style="margin-top:1rem;">
                    <?php foreach ($availableRelatedProducts as $relatedProduct): ?>
                        <?php $relatedProductId = (int) ($relatedProduct['id'] ?? 0); ?>
                        <label class="admin-soft-card admin-card admin-choice-card" style="padding:1rem;display:flex;gap:0.8rem;align-items:flex-start;" data-admin-filter-item data-admin-filter-text="<?php echo htmlspecialchars(trim((string) ($relatedProduct['name'] ?? '') . ' ' . (string) ($relatedProduct['slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                            <input
                                name="related_product_ids[]"
                                type="checkbox"
                                value="<?php echo htmlspecialchars((string) $relatedProductId, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo in_array($relatedProductId, $selectedRelatedProductIds ?? [], true) ? 'checked' : ''; ?>
                                style="width:1rem;height:1rem;margin-top:0.25rem;"
                            >
                            <span>
                                <strong><?php echo htmlspecialchars((string) ($relatedProduct['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="admin-note" style="display:block;"><?php echo htmlspecialchars((string) ($relatedProduct['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="product-images" class="admin-card admin-form-section" style="grid-column:1 / -1;padding:1.1rem;">
            <p class="admin-kicker">Images</p>
            <?php if (!empty($images)): ?>
                <div class="admin-grid cols-3 admin-choice-grid" style="margin-top:1rem;">
                    <?php foreach ($images as $image): ?>
                        <?php $imageId = (int) ($image['id'] ?? 0); ?>
                        <div class="admin-soft-card admin-card admin-media-tile" style="padding:0.8rem;">
                            <img src="<?php echo htmlspecialchars((string) ($image['image_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:100%;aspect-ratio:4/5;object-fit:contain;object-position:center center;border-radius:12px;background:linear-gradient(180deg,#f8eff1 0%,#f2e6e9 100%);padding:0.55rem;">
                            <input type="hidden" name="existing_image_id[]" value="<?php echo htmlspecialchars((string) $imageId, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="admin-field" style="margin-top:0.8rem;">
                                <label for="existing_image_sort_order_<?php echo $imageId; ?>">Sort Order</label>
                                <input id="existing_image_sort_order_<?php echo $imageId; ?>" name="existing_image_sort_order[]" type="number" min="0" step="1" value="<?php echo htmlspecialchars((string) ($image['sort_order'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <label class="admin-checkbox" style="margin-top:0.25rem;">
                                <input type="checkbox" name="remove_image_ids[]" value="<?php echo htmlspecialchars((string) $imageId, ENT_QUOTES, 'UTF-8'); ?>">
                                <span>Remove this image</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="admin-field" style="margin-top:1rem;">
                <label for="images">Upload Images</label>
                <input id="images" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                <p class="admin-note" style="margin-top:0.55rem;">Recommended product photo size: <strong>2400 × 3000 px (4:5)</strong>. Keep the bouquet centered with a little space around edges. WebP preferred, JPG acceptable.</p>
            </div>
        </div>

        <div id="product-variants" class="admin-card admin-form-section" style="grid-column:1 / -1;padding:1.1rem;">
            <div>
                <p class="admin-kicker">Variants</p>
                <p class="admin-note" style="margin:0.35rem 0 0;">Use the fixed Lily and Rose size set. Enable the sizes you want to offer, adjust their price modifiers, and keep the storefront selection flow stable.</p>
                <?php if (!empty($legacyVariantNames)): ?>
                    <p class="admin-note" style="margin-top:0.65rem;color:#8b3c39;">Legacy custom variants detected: <?php echo htmlspecialchars(implode(', ', (array) $legacyVariantNames), ENT_QUOTES, 'UTF-8'); ?>. Saving this product will replace them with the fixed Standard / Deluxe / Premium set below.</p>
                <?php endif; ?>
            </div>
            <div class="admin-grid cols-3 admin-choice-grid" style="margin-top:1rem;">
                <?php foreach (($variants ?? []) as $variant): ?>
                    <?php $variantKey = (string) ($variant['key'] ?? ''); ?>
                    <div class="admin-soft-card admin-card admin-choice-card" style="padding:1rem;">
                        <label class="admin-checkbox" style="margin-bottom:0.8rem;">
                            <input
                                id="variant_enabled_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>"
                                name="variant_enabled[<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>]"
                                type="checkbox"
                                value="1"
                                <?php echo !empty($variant['enabled']) ? 'checked' : ''; ?>
                            >
                            <span>Enable <?php echo htmlspecialchars((string) ($variant['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                        <div class="admin-field">
                            <label for="variant_name_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>">Variant Name</label>
                            <input
                                id="variant_name_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>"
                                type="text"
                                value="<?php echo htmlspecialchars((string) ($variant['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                readonly
                            >
                        </div>
                        <input type="hidden" name="variant_name[<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars((string) ($variant['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="admin-field">
                            <label for="variant_price_modifier_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>">Price Modifier</label>
                            <input
                                id="variant_price_modifier_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>"
                                name="variant_price_modifier[<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>]"
                                type="number"
                                step="0.01"
                                value="<?php echo htmlspecialchars((string) ($variant['price_modifier'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="admin-field">
                            <label for="variant_sort_order_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>">Sort Order</label>
                            <input
                                id="variant_sort_order_<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>"
                                name="variant_sort_order[<?php echo htmlspecialchars($variantKey, ENT_QUOTES, 'UTF-8'); ?>]"
                                type="number"
                                min="0"
                                step="1"
                                value="<?php echo htmlspecialchars((string) ($variant['sort_order'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-form-actions" style="grid-column:1 / -1;display:flex;gap:0.8rem;align-items:center;">
            <button type="submit" class="admin-button"><?php echo ($formMode ?? 'create') === 'edit' ? 'Update Product' : 'Save Product'; ?></button>
            <a href="/admin/products" class="admin-button-secondary">Cancel</a>
        </div>
    </form>
</div>
