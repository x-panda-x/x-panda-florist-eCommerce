<?php
$publicBlocks = public_page_blocks(true);
$productDetailHelper = $publicBlocks['page.product-detail.helper'] ?? [
    'subheading' => 'Arrangement Notes',
    'heading' => 'Helpful notes before adding this arrangement to cart.',
    'body_text' => 'A classic florist product page with live pricing and size selection still driven by the current runtime.',
];
$productDetailRelated = $publicBlocks['page.product-detail.related'] ?? [
    'subheading' => 'Related Arrangements',
    'heading' => 'More bouquets chosen to complement this selection.',
];
?>
<?php if (empty($product)): ?>
    <main class="container py-5 text-center">
        <h1 class="page-title">Product Not Found</h1>
        <p class="page-subtitle">The requested bouquet could not be found.</p>
        <a href="/" class="btn mt-2">Return Home</a>
    </main>
<?php else: ?>
    <?php $images = is_array($product['images'] ?? null) ? $product['images'] : []; ?>
    <?php $addons = is_array($product['addons'] ?? null) ? $product['addons'] : []; ?>
    <?php $relatedProducts = is_array($product['related_products'] ?? null) ? $product['related_products'] : []; ?>
    <?php $primaryImage = !empty($product['image_path']) ? (string) $product['image_path'] : '/assets/images/placeholder-bouquet.jpg'; ?>
    <?php $galleryImages = []; ?>
    <?php if ($primaryImage !== '') { $galleryImages[] = $primaryImage; } ?>
    <?php foreach ($images as $image) {
        $img = (string) ($image['image_path'] ?? '');
        if ($img !== '' && !in_array($img, $galleryImages, true)) { $galleryImages[] = $img; }
    } ?>

    <main class="container pdp-wrap">
        <!-- LEFT: Gallery -->
        <div class="pdp-gallery">
            <?php if ($galleryImages !== []): ?>
                <div class="pdp-main-image-frame">
                    <img id="pdp-main-image" class="pdp-main-image" src="<?php echo htmlspecialchars($galleryImages[0], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--color-gray-mid);">No Image</div>
            <?php endif; ?>

            <?php if (count($galleryImages) > 1): ?>
                <div class="radio-pill-list" style="margin-top:1rem;">
                    <?php foreach ($galleryImages as $index => $galleryImage): ?>
                        <button
                            type="button"
                            class="radio-pill-content"
                            style="border:1px solid var(--color-gray-light);background:#fff;padding:0;overflow:hidden;cursor:pointer;"
                            onclick="document.getElementById('pdp-main-image').src='<?php echo htmlspecialchars($galleryImage, ENT_QUOTES, 'UTF-8'); ?>';"
                            aria-label="View image <?php echo htmlspecialchars((string) ($index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <img src="<?php echo htmlspecialchars($galleryImage, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="pdp-gallery-thumb">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Details & Form -->
        <div class="pdp-info">
            <nav aria-label="Breadcrumb" class="pdp-breadcrumbs">
                <a href="/">Home</a> > 
                <a href="/best-sellers">Flowers</a> > 
                <span><?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </nav>

            <?php if (!empty($cartError)): ?>
                <div class="admin-alert error">
                    <?php echo htmlspecialchars((string) $cartError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <h1 class="pdp-title"><?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="pdp-price">
                $<?php echo htmlspecialchars(number_format((float) ($product['display_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <form method="post" action="/cart/add" class="pdp-add-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_slug" value="<?php echo htmlspecialchars((string) ($product['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                <?php if (!empty($product['variants'])): ?>
                    <div class="variant-group">
                        <label class="variant-label">Select Size</label>
                        <div class="radio-pill-list">
                            <?php foreach ($product['variants'] as $index => $variant): ?>
                                <?php 
                                    $vPrice = max(0, (float) ($product['base_price'] ?? 0) + (float) ($variant['price_modifier'] ?? 0)); 
                                    $vName = (string) ($variant['name'] ?? '');
                                ?>
                                <label class="radio-pill">
                                    <input type="radio" name="variant_id" value="<?php echo htmlspecialchars((string) $variant['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <div class="radio-pill-content">
                                        <span><?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span>$<?php echo htmlspecialchars(number_format($vPrice, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($addons !== []): ?>
                    <div class="variant-group">
                        <label class="variant-label">Make It Extra Special</label>
                        <div class="addon-list">
                            <?php foreach ($addons as $addon): ?>
                                <label class="addon-item">
                                    <input type="checkbox" name="addon_ids[]" value="<?php echo htmlspecialchars((string) $addon['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div style="flex-grow:1;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                                            <strong style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.05em;font-size:0.85rem;"><?php echo htmlspecialchars((string) $addon['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span style="font-weight:500;font-size:0.85rem;color:var(--color-gray-dark);">+$<?php echo htmlspecialchars(number_format((float) ($addon['price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <?php if (!empty($addon['description'])): ?>
                                            <div class="site-note" style="color:var(--color-gray-mid);font-size:0.8rem;line-height:1.4;"><?php echo htmlspecialchars((string) ($addon['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="qty-wrap">
                    <input type="number" name="quantity" min="1" value="1" class="qty-input" required>
                    <button type="submit" class="btn btn-block">ADD TO BAG</button>
                </div>
            </form>

            <div style="margin-top:4rem;">
                <label class="variant-label">Description</label>
                <div style="color:var(--color-gray-dark);line-height:1.6;font-size:0.9rem;">
                    <?php if (!empty($product['description'])): ?>
                        <?php echo nl2br(htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8')); ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars((string) ($productDetailHelper['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if ($relatedProducts !== []): ?>
        <div class="container" style="margin-top:2rem;border-top:1px solid var(--color-gray-light);padding-top:4rem;margin-bottom:5rem;">
            <div style="text-align:center;max-width:600px;margin:0 auto 3rem;">
                <p class="eyebrow" style="color:var(--color-gray-dark);margin-bottom:0.5rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;">Complete The Gift</p>
                <h2 style="font-family:var(--font-heading);font-size:1.5rem;font-weight:500;text-transform:uppercase;letter-spacing:0.1em;color:var(--color-black);margin:0;">RECOMMENDED FOR YOU</h2>
            </div>
            <div class="product-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));margin-bottom:0;">
                <?php foreach (array_slice($relatedProducts, 0, 4) as $relatedProduct): ?>
                    <?php $imgSrc = !empty($relatedProduct['image_path']) ? $relatedProduct['image_path'] : '/assets/images/placeholder-bouquet.jpg'; ?>
                    <a href="/product?slug=<?php echo urlencode((string) ($relatedProduct['slug'] ?? '')); ?>" class="product-card">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars((string) $imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars((string) ($relatedProduct['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="product-price">$<?php echo htmlspecialchars(number_format((float) ($relatedProduct['display_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
