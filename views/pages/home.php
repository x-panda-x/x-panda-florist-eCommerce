<?php
$storeName = (string) settings('store_name', $pageTitle ?? 'Lily and Rose');
$storeEmail = (string) settings('store_email', 'Email coming soon');
$storePhone = (string) settings('store_phone', 'Phone coming soon');
$sameDayCutoff = (string) settings('same_day_cutoff', 'Cutoff posted at checkout');
$heroProduct = !empty($products) && is_array($products) ? $products[0] : null;
$filters = is_array($filters ?? null) ? $filters : [];
$sortOptions = is_array($sortOptions ?? null) ? $sortOptions : [];
$categoryOptions = is_array($categoryOptions ?? null) ? $categoryOptions : [];
$occasionOptions = is_array($occasionOptions ?? null) ? $occasionOptions : [];
$homepageProductSections = is_array($homepageProductSections ?? null) ? $homepageProductSections : [];

$managedHomepageBlocks = homepage_blocks(true);
$fallbackHomepageBlocks = [
    'home.hero' => [
        'subheading' => 'SHOP NEW-IN FLOWERS',
        'heading' => 'FEELS LIKE SPRING',
        'cta_label' => 'Shop Best Sellers',
        'cta_url' => '/best-sellers',
    ],
    'home.seo' => [
        'subheading' => 'LOCAL FLOWER DELIVERY',
        'heading' => 'Send fresh flowers for birthdays, sympathy, anniversaries, and everyday moments.',
        'body_text' => 'Seasonal arrangements, same-day delivery zones, and florist-designed bouquets from the live catalog.',
    ],
    'home.feature-intro' => [
        'subheading' => 'SIGNATURE COLLECTION',
        'heading' => 'DESIGNED FOR IMPACT',
        'body_text' => 'Our florists carefully curate each stem to ensure unmatched beauty and longevity.',
    ],
];

$resolveHomepageBlock = static function (string $blockKey) use ($managedHomepageBlocks, $fallbackHomepageBlocks): array {
    $managedBlock = $managedHomepageBlocks[$blockKey] ?? null;
    return is_array($managedBlock) ? $managedBlock : ($fallbackHomepageBlocks[$blockKey] ?? []);
};

$heroBlock = $resolveHomepageBlock('home.hero');
$seoBlock = $resolveHomepageBlock('home.seo');
$quickLinksBlock = $resolveHomepageBlock('home.quick-links');
$featureIntroBlock = $resolveHomepageBlock('home.feature-intro');
$featuresBlock = $resolveHomepageBlock('home.features');
$trustBlock = $resolveHomepageBlock('home.trust');
$newsletterBlock = $resolveHomepageBlock('home.newsletter');
$heroMediaAsset = is_array($heroBlock['media_asset'] ?? null) ? $heroBlock['media_asset'] : null;
$heroActions = [];
$quickLinkItems = is_array($quickLinksBlock['items'] ?? null) ? $quickLinksBlock['items'] : [];
$featureItems = is_array($featuresBlock['items'] ?? null) ? $featuresBlock['items'] : [];
$trustItems = is_array($trustBlock['items'] ?? null) ? $trustBlock['items'] : [];

if (!empty($heroBlock['cta_label']) && !empty($heroBlock['cta_url'])) {
    $heroActions[] = [
        'label' => (string) $heroBlock['cta_label'],
        'url' => (string) $heroBlock['cta_url'],
    ];
}
?>
<main class="page-home">
    <!-- HERO FULL SCREEN -->
    <div class="hero-split">
        <div class="hero-image">
            <?php if (!empty($heroMediaAsset['public_path'])): ?>
                <img src="<?php echo htmlspecialchars((string) ($heroMediaAsset['public_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="Hero">
            <?php elseif (!empty($heroProduct['image_path'])): ?>
                <img src="<?php echo htmlspecialchars((string) $heroProduct['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Hero">
            <?php endif; ?>
        </div>
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars((string) ($heroBlock['heading'] ?? 'FEELS LIKE SPRING'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars((string) ($heroBlock['subheading'] ?? 'SHOP NEW-IN FLOWERS'), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($heroActions !== []): ?>
                <a href="<?php echo htmlspecialchars((string) $heroActions[0]['url'], ENT_QUOTES, 'UTF-8'); ?>" class="btn"><?php echo htmlspecialchars((string) ($heroActions[0]['label'] ?? 'SHOP SPRING'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
                <a href="/occasions" class="btn">Shop Best Sellers</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($quickLinkItems !== []): ?>
        <?php
        $categoryIcons = [
            'Birthday' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z"></path></svg>',
            'Sympathy' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>',
            'Best Sellers' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>',
            'Same Day' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
            'Flowers' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>',
            'Gifts + Food' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>',
            'default' => '<svg class="w-8 h-8 mx-auto mb-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>'
        ];
        ?>
        <div class="category-strip">
            <div class="container">
                <div class="category-strip-grid">
                    <?php foreach ($quickLinkItems as $item): ?>
                        <?php if (!empty($item['cta_url']) && !empty($item['title'])): 
                            $title = htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $icon = $categoryIcons[$title] ?? $categoryIcons['default'];
                        ?>
                            <a href="<?php echo htmlspecialchars((string) ($item['cta_url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>" class="category-box">
                                <div class="category-icon"><?php echo $icon; ?></div>
                                <div class="category-title"><?php echo $title; ?></div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($homepageProductSections !== []): ?>
        <div class="homepage-product-sections">
            <div class="container">
                <?php foreach ($homepageProductSections as $sectionIndex => $homepageSection): ?>
                    <?php $displayProducts = is_array($homepageSection['products'] ?? null) ? $homepageSection['products'] : []; ?>
                    <?php if ($displayProducts === []): ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <section class="homepage-product-section">
                        <div class="homepage-product-section__header">
                            <div>
                                <?php if (!empty($homepageSection['subheading'])): ?>
                                    <p class="eyebrow homepage-product-section__eyebrow"><?php echo htmlspecialchars((string) ($homepageSection['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <h2><?php echo htmlspecialchars((string) ($homepageSection['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                            <?php if (!empty($homepageSection['cta_label']) && !empty($homepageSection['cta_url'])): ?>
                                <a href="<?php echo htmlspecialchars((string) ($homepageSection['cta_url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-secondary homepage-product-section__link"><?php echo htmlspecialchars((string) ($homepageSection['cta_label'] ?? 'View All'), ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php endif; ?>
                        </div>

                        <div class="homepage-slider" data-homepage-slider>
                            <button type="button" class="homepage-slider__button homepage-slider__button--prev" data-slider-prev aria-label="Previous products in <?php echo htmlspecialchars((string) ($homepageSection['title'] ?? 'section'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span aria-hidden="true">&lsaquo;</span>
                            </button>

                            <div class="homepage-slider__track" data-slider-track tabindex="0" aria-label="<?php echo htmlspecialchars((string) ($homepageSection['title'] ?? 'Homepage products'), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach ($displayProducts as $product): ?>
                                    <?php $imgSrc = !empty($product['image_path']) ? $product['image_path'] : '/assets/images/placeholder-bouquet.jpg'; ?>
                                    <a href="/product?slug=<?php echo urlencode((string) ($product['slug'] ?? '')); ?>" class="product-card homepage-slider__card">
                                        <div class="product-image">
                                            <img src="<?php echo htmlspecialchars((string) $imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="product-info">
                                            <div class="product-title"><?php echo htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="product-price">$<?php echo htmlspecialchars(number_format((float) ($product['display_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" class="homepage-slider__button homepage-slider__button--next" data-slider-next aria-label="Next products in <?php echo htmlspecialchars((string) ($homepageSection['title'] ?? 'section'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span aria-hidden="true">&rsaquo;</span>
                            </button>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($featureIntroBlock['heading']) || !empty($featureItems)): ?>
        <div style="background: linear-gradient(180deg, var(--color-off-white) 0%, var(--color-blush) 100%); padding: 5rem 0; margin: 4rem 0;">
            <div class="container">
                <?php if (!empty($featureIntroBlock['heading'])): ?>
                    <div style="text-align:center;max-width:640px;margin:0 auto 3rem;">
                        <?php if (!empty($featureIntroBlock['subheading'])): ?>
                            <p class="eyebrow" style="color:rgba(0,0,0,0.5); font-size:0.75rem; letter-spacing:0.15em; margin-bottom: 0.75rem;"><?php echo htmlspecialchars((string) ($featureIntroBlock['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <h2 style="font-family:var(--font-heading);font-size:2.2rem;font-weight:400;text-transform:uppercase;letter-spacing:0.15em;color:var(--color-black);margin:0;"><?php echo htmlspecialchars((string) ($featureIntroBlock['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php if (!empty($featureIntroBlock['body_text'])): ?>
                            <p class="page-subtitle" style="margin-top:1rem;color:var(--color-gray-dark);"><?php echo htmlspecialchars((string) ($featureIntroBlock['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($featureItems !== []): ?>
                    <div class="product-grid" style="grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); border: none; background: transparent;">
                        <?php foreach ($featureItems as $item): ?>
                            <div class="product-card" style="padding:2rem;text-align:center;background:var(--color-white);border-radius:4px;box-shadow:0 10px 30px rgba(0,0,0,0.03);">
                                <div class="product-title" style="font-size:1rem;margin-bottom:0.5rem;"><?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php if (!empty($item['body_text'])): ?>
                                    <div class="site-note" style="color:var(--color-gray-mid);line-height:1.5;"><?php echo htmlspecialchars((string) ($item['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- SEO TEXT -->
    <div class="container" style="text-align: center; max-width: 600px; padding: 2rem 1.5rem; border-top: 1px solid var(--color-gray-light); margin-bottom: 2rem;">
        <h2 style="font-family: var(--font-heading); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; color: var(--color-gray-dark); margin-bottom: 0.5rem;"><?php echo htmlspecialchars((string) ($seoBlock['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p style="font-family: var(--font-body); font-size: 0.85rem; color: var(--color-gray-mid); margin: 0 auto; line-height: 1.6;"><?php echo htmlspecialchars((string) ($seoBlock['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if ($trustItems !== [] || !empty($newsletterBlock['heading'])): ?>
        <div class="container" style="padding-bottom:4rem;">
            <?php if ($trustItems !== []): ?>
                <div class="product-grid" style="grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));margin-bottom:2rem;">
                    <?php foreach ($trustItems as $item): ?>
                        <div class="product-card" style="padding:1.5rem;text-align:center;">
                            <div class="product-title"><?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($item['body_text'])): ?>
                                <div class="site-note" style="margin-top:0.75rem;"><?php echo htmlspecialchars((string) ($item['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($newsletterBlock['heading'])): ?>
                <div style="text-align:center;max-width:640px;margin:0 auto;">
                    <?php if (!empty($newsletterBlock['subheading'])): ?>
                        <p class="eyebrow"><?php echo htmlspecialchars((string) ($newsletterBlock['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <h2 class="section-title"><?php echo htmlspecialchars((string) ($newsletterBlock['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (!empty($newsletterBlock['body_text'])): ?>
                        <p class="page-subtitle"><?php echo htmlspecialchars((string) ($newsletterBlock['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-homepage-slider]').forEach((slider) => {
            const track = slider.querySelector('[data-slider-track]');
            const previous = slider.querySelector('[data-slider-prev]');
            const next = slider.querySelector('[data-slider-next]');

            if (!track || !previous || !next) {
                return;
            }

            const scrollAmount = () => Math.max(260, Math.floor(track.clientWidth * 0.85));
            const syncButtons = () => {
                const maxScroll = track.scrollWidth - track.clientWidth;
                const atStart = track.scrollLeft <= 4;
                const atEnd = track.scrollLeft >= maxScroll - 4;

                previous.disabled = atStart;
                next.disabled = atEnd || maxScroll <= 4;
            };
            let scrollFrame = 0;

            previous.addEventListener('click', () => {
                track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
            });

            next.addEventListener('click', () => {
                track.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
            });

            track.addEventListener('scroll', () => {
                window.cancelAnimationFrame(scrollFrame);
                scrollFrame = window.requestAnimationFrame(syncButtons);
            }, { passive: true });

            window.addEventListener('resize', syncButtons);
            syncButtons();
        });
    });
    </script>
</main>
