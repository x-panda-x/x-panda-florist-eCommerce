<?php
$storeName = (string) settings('store_name', 'Lily and Rose');
$storePhone = (string) settings('store_phone', '');
$storeEmail = (string) settings('store_email', '');
$cutoff = (string) settings('same_day_cutoff', '');
$socials = [
    'Instagram' => (string) settings('instagram_url', ''),
    'Facebook' => (string) settings('facebook_url', ''),
    'X' => (string) settings('x_url', ''),
    'TikTok' => (string) settings('tiktok_url', ''),
];
$footerBlocks = footer_blocks(true);
$fallbackFooter = [
    'global.footer.about' => [
        'heading' => $storeName !== '' ? $storeName : 'Local Florist',
        'body_text' => 'Fresh arrangements, occasion flowers, and same-day orders backed by the live storefront and admin system.',
        'items' => [
            ['title' => 'Store phone: ' . ($storePhone !== '' ? $storePhone : 'Available in settings'), 'cta_url' => null],
            ['title' => 'Store email: ' . ($storeEmail !== '' ? $storeEmail : 'Available in settings'), 'cta_url' => null],
        ],
    ],
    'global.footer.shop' => [
        'heading' => 'Shop',
        'items' => [
            ['title' => 'Home', 'cta_url' => '/'],
            ['title' => 'Best Sellers', 'cta_url' => '/best-sellers'],
            ['title' => 'Occasions', 'cta_url' => '/occasions'],
            ['title' => 'Search', 'cta_url' => '/search'],
        ],
    ],
    'global.footer.service' => [
        'heading' => 'Help & Information',
        'items' => [
            ['title' => 'Contact Us', 'cta_url' => '/contact'],
            ['title' => 'Track Order', 'cta_url' => '/order-status'],
            ['title' => 'Cart', 'cta_url' => '/cart'],
            ['title' => 'Checkout', 'cta_url' => '/checkout'],
        ],
    ],
    'global.footer.business' => [
        'heading' => 'Account',
        'items' => [
            ['title' => 'Sign In', 'cta_url' => '/account/login'],
            ['title' => 'Register', 'cta_url' => '/account/register'],
            ['title' => 'Forgot Password', 'cta_url' => '/account/forgot-password'],
        ],
    ],
    'global.footer.bottom' => [
        'body_text' => 'Powered by the live Lily and Rose storefront, checkout, account, and admin runtime.',
    ],
];

$aboutBlock = $footerBlocks['global.footer.about'] ?? $fallbackFooter['global.footer.about'];
$shopBlock = $footerBlocks['global.footer.shop'] ?? $fallbackFooter['global.footer.shop'];
$serviceBlock = $footerBlocks['global.footer.service'] ?? $fallbackFooter['global.footer.service'];
$businessBlock = $footerBlocks['global.footer.business'] ?? $fallbackFooter['global.footer.business'];
$bottomBlock = $footerBlocks['global.footer.bottom'] ?? $fallbackFooter['global.footer.bottom'];
?>
<footer class="site-footer">
    <div class="container">
        <div style="text-align:center; max-width:600px; margin:0 auto 4rem;">
            <h3 style="font-family:var(--font-heading);font-weight:500;text-transform:uppercase;letter-spacing:0.15em;margin-bottom:1rem;color:var(--color-black);">Visit The Shop</h3>
            <p style="color:var(--color-gray-dark);margin-bottom:0;font-size:0.9rem;">
                <?php echo htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($storePhone !== ''): ?> | <?php echo htmlspecialchars($storePhone, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                <?php if ($storeEmail !== ''): ?> | <?php echo htmlspecialchars($storeEmail, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </p>
        </div>

        <div class="footer-grid">
            <div class="footer-col">
                <h4><?php echo htmlspecialchars((string) ($serviceBlock['heading'] ?? 'HELP & INFORMATION'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="footer-links-list">
                    <?php foreach (($serviceBlock['items'] ?? []) as $item): ?>
                        <?php if (!empty($item['cta_url'])): ?>
                            <a href="<?php echo htmlspecialchars((string) ($item['cta_url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper((string) ($item['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <span><?php echo htmlspecialchars(strtoupper((string) ($item['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="footer-col">
                <h4><?php echo htmlspecialchars((string) ($shopBlock['heading'] ?? 'EXPLORE'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="footer-links-list">
                    <?php foreach (($shopBlock['items'] ?? []) as $item): ?>
                        <?php if (!empty($item['cta_url'])): ?>
                            <a href="<?php echo htmlspecialchars((string) ($item['cta_url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper((string) ($item['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <span><?php echo htmlspecialchars(strtoupper((string) ($item['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="footer-col">
                <h4><?php echo htmlspecialchars((string) ($businessBlock['heading'] ?? 'ABOUT US'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="footer-links-list">
                    <?php foreach (($businessBlock['items'] ?? []) as $item): ?>
                        <?php if (!empty($item['cta_url'])): ?>
                            <a href="<?php echo htmlspecialchars((string) ($item['cta_url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper((string) ($item['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <span><?php echo htmlspecialchars(strtoupper((string) ($item['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="footer-col">
                <h4>SOCIAL</h4>
                <div class="footer-links-list">
                    <?php foreach ($socials as $label => $url): ?>
                        <?php if ($url !== ''): ?>
                            <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper($label), ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="footer-col">
                <h4><?php echo htmlspecialchars((string) ($aboutBlock['heading'] ?? 'LOCAL FLORIST'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <p style="font-size:0.85rem;color:var(--color-gray-dark);margin-bottom:1rem;"><?php echo htmlspecialchars((string) ($aboutBlock['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <div class="footer-bottom">
            <div style="text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">
                &copy; <?php echo htmlspecialchars((string) date('Y'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars(strtoupper($storeName), ENT_QUOTES, 'UTF-8'); ?>. ALL RIGHTS RESERVED.
            </div>
            <div style="font-size:0.75rem;">
                <?php echo htmlspecialchars((string) ($bottomBlock['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>
</footer>
