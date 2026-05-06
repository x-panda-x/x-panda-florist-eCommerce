<?php
$filters = is_array($filters ?? null) ? $filters : [];
$sortOptions = is_array($sortOptions ?? null) ? $sortOptions : [];
$categoryOptions = is_array($categoryOptions ?? null) ? $categoryOptions : [];
$occasionOptions = is_array($occasionOptions ?? null) ? $occasionOptions : [];
$hasActiveBrowseFilters = (($filters['query'] ?? '') !== '')
    || (($filters['sort'] ?? '') !== '')
    || (($filters['category'] ?? '') !== '')
    || (($filters['occasion'] ?? '') !== '')
    || !empty($filters['featured_only']);
$publicBlocks = public_page_blocks(true);
$occasionsIntro = $publicBlocks['page.occasions.intro'] ?? [
    'subheading' => 'LUXURY GIFTING',
    'heading' => 'SHOP BY OCCASION',
    'body_text' => 'Browse occasion groupings built from the live product-to-occasion assignments.',
];
?>
<main class="page-listing">
    <!-- HERO BANNER -->
    <div style="background:var(--color-off-white);padding:4rem 1rem;text-align:center;margin-bottom:2rem;border-bottom:1px solid var(--color-gray-light);">
        <div class="container">
            <h1 style="font-family:var(--font-heading);color:var(--color-black);font-size:3rem;font-weight:500;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.15em;">
                <?php echo htmlspecialchars((string) ($occasionsIntro['heading'] ?? 'SHOP BY OCCASION'), ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p style="color:var(--color-gray-dark);font-size:0.9rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 auto;">
                <?php echo htmlspecialchars((string) ($occasionsIntro['subheading'] ?? 'LUXURY GIFTING'), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <?php if (!empty($occasionsIntro['body_text'])): ?>
                <p style="color:var(--color-gray-dark);font-size:0.95rem;max-width:42rem;margin:1rem auto 0;line-height:1.7;text-transform:none;letter-spacing:normal;">
                    <?php echo htmlspecialchars((string) ($occasionsIntro['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- LISTING TOOLBAR -->
    <div class="container mb-4">
        <form method="get" action="/occasions" style="padding:1.5rem 0;border-bottom:1px solid var(--color-gray-light);display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label for="occasion_q" style="display:block;font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--color-black);margin-bottom:0.5rem;letter-spacing:0.1em;">Search</label>
                <input id="occasion_q" name="q" type="search" value="<?php echo htmlspecialchars((string) ($filters['query'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search flowers..." style="width:100%;padding:0.75rem;border:1px solid var(--color-gray-light);border-radius:0;">
            </div>
            <div style="flex:1;min-width:150px;">
                <label for="occasion_sort" style="display:block;font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--color-black);margin-bottom:0.5rem;letter-spacing:0.1em;">Sort</label>
                <select id="occasion_sort" name="sort" style="width:100%;padding:0.75rem;border:1px solid var(--color-gray-light);border-radius:0;">
                    <?php foreach ($sortOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars((string) ($option['value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($filters['sort'] ?? '') === (string) ($option['value'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($option['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;min-width:150px;">
                <label for="occasion_category" style="display:block;font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--color-black);margin-bottom:0.5rem;letter-spacing:0.1em;">Category</label>
                <select id="occasion_category" name="category" style="width:100%;padding:0.75rem;border:1px solid var(--color-gray-light);border-radius:0;">
                    <option value="">All categories</option>
                    <?php foreach ($categoryOptions as $category): ?>
                        <option value="<?php echo htmlspecialchars((string) ($category['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($filters['category'] ?? '') === (string) ($category['slug'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;min-width:150px;">
                <label for="occasion_filter" style="display:block;font-size:0.7rem;font-weight:600;text-transform:uppercase;color:var(--color-black);margin-bottom:0.5rem;letter-spacing:0.1em;">Occasion</label>
                <select id="occasion_filter" name="occasion" style="width:100%;padding:0.75rem;border:1px solid var(--color-gray-light);border-radius:0;">
                    <option value="">All occasions</option>
                    <?php foreach ($occasionOptions as $occasion): ?>
                        <option value="<?php echo htmlspecialchars((string) ($occasion['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($filters['occasion'] ?? '') === (string) ($occasion['slug'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($occasion['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:1rem;align-items:center;min-width:0;justify-content:flex-end;flex:1 1 100%;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.75rem;font-weight:600;color:var(--color-black);cursor:pointer;white-space:nowrap;text-transform:uppercase;letter-spacing:0.05em;">
                    <input name="featured" type="checkbox" value="1" <?php echo !empty($filters['featured_only']) ? 'checked' : ''; ?> style="accent-color:var(--color-black);width:1.2rem;height:1.2rem;margin:0;">
                    Featured only
                </label>
                <?php if ($hasActiveBrowseFilters): ?>
                    <a href="/occasions" class="btn-secondary" style="padding:0.75rem 1.5rem;">Clear</a>
                <?php endif; ?>
                <button type="submit" class="btn" style="padding:0.75rem 2rem;">Apply</button>
            </div>
        </form>
    </div>

    <!-- PRODUCT COLLECTIONS GRID -->
    <div class="container mb-5">
        <?php if (empty($occasionCollections)): ?>
            <div style="text-align:center;padding:5rem;border-bottom:1px solid var(--color-gray-light);">
                <?php if ($hasActiveBrowseFilters): ?>
                    <h2 class="section-title">No occasions matched</h2>
                    <p style="color:var(--color-gray-dark);">Try removing a category, occasion, or search term.</p>
                <?php else: ?>
                    <h2 class="section-title">No occasion collections</h2>
                    <p style="color:var(--color-gray-dark);">Check back soon for our latest selections.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:4rem;">
                <?php foreach ($occasionCollections as $occasion): ?>
                    <section>
                        <div style="margin-bottom:1.5rem;border-bottom:1px solid var(--color-gray-light);padding-bottom:1rem;display:flex;justify-content:space-between;align-items:flex-end;">
                            <div>
                                <p class="eyebrow" style="color:var(--color-gray-dark);margin-bottom:0.25rem;">Occasion Collection</p>
                                <h2 style="color:var(--color-black);font-size:1.8rem;font-weight:500;font-family:var(--font-heading);text-transform:uppercase;margin:0;letter-spacing:0.1em;"><?php echo htmlspecialchars((string) ($occasion['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </div>

                        <div class="product-grid" style="grid-template-columns:repeat(auto-fill,minmax(min(280px, 100%),1fr));">
                            <?php if (empty($occasion['products'])): ?>
                                <div style="grid-column:1 / -1;text-align:center;padding:3rem;background:var(--color-off-white);color:var(--color-gray-dark);">
                                    No products assigned yet.
                                </div>
                            <?php else: ?>
                                <?php foreach ($occasion['products'] as $product): ?>
                                    <a href="/product?slug=<?php echo urlencode((string) ($product['slug'] ?? '')); ?>" class="product-card">
                                        <div class="product-image">
                                            <?php if (!empty($product['image_path'])): ?>
                                                <img src="<?php echo htmlspecialchars((string) $product['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php else: ?>
                                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--color-gray-light);color:var(--color-gray-dark);font-size:0.8rem;text-transform:uppercase;">No Image</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info">
                                            <div class="product-title"><?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="product-price">$<?php echo htmlspecialchars(number_format((float) ($product['display_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
