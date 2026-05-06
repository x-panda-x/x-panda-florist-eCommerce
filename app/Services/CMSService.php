<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class CMSService
{
    /**
     * @var array<int, string>
     */
    private const HOMEPAGE_BLOCK_KEYS = [
        'home.hero',
        'home.quick-links',
        'home.feature-intro',
        'home.features',
        'home.trust',
        'home.newsletter',
        'home.seo',
    ];

    /**
     * @var array<int, string>
     */
    private const PUBLIC_PAGE_BLOCK_KEYS = [
        'page.contact.hero',
        'page.contact.support',
        'page.order-status.intro',
        'page.order-status.empty',
        'page.checkout.help',
        'page.payment.help',
        'page.order-confirmation.help',
        'page.best-sellers.intro',
        'page.occasions.intro',
        'page.search.intro',
        'page.search.empty',
        'page.product-detail.helper',
        'page.product-detail.related',
    ];

    /**
     * @var array<int, string>
     */
    private const FOOTER_BLOCK_KEYS = [
        'global.footer.about',
        'global.footer.shop',
        'global.footer.service',
        'global.footer.business',
        'global.footer.bottom',
    ];

    private Application $app;
    private MediaService $mediaService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->mediaService = new MediaService($app);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBlockByKey(string $blockKey, bool $includeItems = false): ?array
    {
        $blockKey = trim($blockKey);

        if ($blockKey === '') {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM content_blocks
             WHERE block_key = :block_key
             LIMIT 1',
            ['block_key' => $blockKey]
        )->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrateBlock($row, $includeItems);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBlocksByPage(string $pageKey, bool $enabledOnly = true, bool $includeItems = false): array
    {
        $pageKey = trim($pageKey);

        if ($pageKey === '') {
            return [];
        }

        $sql = 'SELECT *
                FROM content_blocks
                WHERE page_key = :page_key';
        $params = ['page_key' => $pageKey];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $rows = $this->app->database()->fetchAll($sql, $params);

        return array_map(
            fn (array $row): array => $this->hydrateBlock($row, $includeItems),
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBlockItems(int $contentBlockId, bool $enabledOnly = true): array
    {
        if ($contentBlockId <= 0) {
            return [];
        }

        $sql = 'SELECT *
                FROM content_block_items
                WHERE content_block_id = :content_block_id';
        $params = ['content_block_id' => $contentBlockId];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $rows = $this->app->database()->fetchAll($sql, $params);

        return array_map(fn (array $row): array => $this->hydrateBlockItem($row), $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBannerByKey(string $bannerKey, bool $enabledOnly = true): ?array
    {
        $bannerKey = trim($bannerKey);

        if ($bannerKey === '') {
            return null;
        }

        $sql = 'SELECT *
                FROM banners
                WHERE banner_key = :banner_key';
        $params = ['banner_key' => $bannerKey];

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC
                  LIMIT 1';

        $row = $this->app->database()->query($sql, $params)->fetch();

        return is_array($row) ? $this->hydrateBanner($row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBannerById(int $bannerId): ?array
    {
        if ($bannerId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM banners
             WHERE id = :id
             LIMIT 1',
            ['id' => $bannerId]
        )->fetch();

        return is_array($row) ? $this->hydrateBanner($row) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBanners(string $pageKey, ?string $placement = null, bool $enabledOnly = true): array
    {
        $pageKey = trim($pageKey);
        $placement = $placement !== null ? trim($placement) : null;

        if ($pageKey === '') {
            return [];
        }

        $sql = 'SELECT *
                FROM banners
                WHERE page_key = :page_key';
        $params = ['page_key' => $pageKey];

        if ($placement !== null && $placement !== '') {
            $sql .= ' AND placement = :placement';
            $params['placement'] = $placement;
        }

        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $rows = $this->app->database()->fetchAll($sql, $params);

        return array_map(fn (array $row): array => $this->hydrateBanner($row), $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllBanners(): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT *
             FROM banners
             ORDER BY page_key ASC, placement ASC, sort_order ASC, id ASC'
        );

        return array_map(fn (array $row): array => $this->hydrateBanner($row), $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createBanner(array $data): void
    {
        $this->app->database()->execute(
            'INSERT INTO banners (
                banner_key,
                page_key,
                placement,
                title,
                subtitle,
                body_text,
                cta_label,
                cta_url,
                media_asset_id,
                is_enabled,
                starts_at,
                ends_at,
                sort_order
             ) VALUES (
                :banner_key,
                :page_key,
                :placement,
                :title,
                :subtitle,
                :body_text,
                :cta_label,
                :cta_url,
                :media_asset_id,
                :is_enabled,
                :starts_at,
                :ends_at,
                :sort_order
             )',
            $this->normalizeBannerPersistData($data)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateBanner(int $bannerId, array $data): void
    {
        if ($this->findBannerById($bannerId) === null) {
            throw new \RuntimeException('Banner not found.');
        }

        $payload = $this->normalizeBannerPersistData($data);
        $payload['id'] = $bannerId;

        $this->app->database()->execute(
            'UPDATE banners
             SET banner_key = :banner_key,
                 page_key = :page_key,
                 placement = :placement,
                 title = :title,
                 subtitle = :subtitle,
                 body_text = :body_text,
                 cta_label = :cta_label,
                 cta_url = :cta_url,
                 media_asset_id = :media_asset_id,
                 is_enabled = :is_enabled,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 sort_order = :sort_order
             WHERE id = :id',
            $payload
        );
    }

    public function deleteBanner(int $bannerId): void
    {
        if ($this->findBannerById($bannerId) === null) {
            throw new \RuntimeException('Banner not found.');
        }

        $this->app->database()->execute(
            'DELETE FROM banners
             WHERE id = :id',
            ['id' => $bannerId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeBannerForPlacement(string $placement, string $pageKey = 'global'): ?array
    {
        $placement = trim($placement);
        $pageKey = trim($pageKey);

        if ($placement === '' || $pageKey === '') {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM banners
             WHERE placement = :placement
               AND page_key = :page_key
               AND is_enabled = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY sort_order ASC, id ASC
             LIMIT 1',
            [
                'placement' => $placement,
                'page_key' => $pageKey,
            ]
        )->fetch();

        return is_array($row) ? $this->hydrateBanner($row) : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getFooterBlocks(bool $enabledOnly = true): array
    {
        $this->ensureFooterFoundation();

        $blocks = [];

        foreach (self::FOOTER_BLOCK_KEYS as $blockKey) {
            $block = $this->findBlockByKey($blockKey, true);

            if ($block === null) {
                continue;
            }

            if ($enabledOnly && empty($block['is_enabled'])) {
                continue;
            }

            $blocks[$blockKey] = $block;
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function saveFooterBlocks(array $payload): void
    {
        $definitions = $this->normalizeFooterPayload($payload);

        foreach ($definitions as $definition) {
            $blockId = $this->upsertContentBlock($definition['block']);
            $this->replaceContentBlockItems($blockId, $definition['items']);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getHomepageBlocks(bool $enabledOnly = true): array
    {
        $this->ensureHomepageFoundation();

        $blocks = [];

        foreach (self::HOMEPAGE_BLOCK_KEYS as $blockKey) {
            $block = $this->findBlockByKey($blockKey, true);

            if ($block === null) {
                continue;
            }

            if ($enabledOnly && empty($block['is_enabled'])) {
                continue;
            }

            $blocks[$blockKey] = $block;
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getHomepageHeroBlock(bool $enabledOnly = false): ?array
    {
        $this->ensureHomepageFoundation();
        $block = $this->findBlockByKey('home.hero', true);

        if ($block === null) {
            return null;
        }

        if ($enabledOnly && empty($block['is_enabled'])) {
            return null;
        }

        return $block;
    }

    public function setHomepageHeroMediaAssetId(?int $assetId): void
    {
        $this->ensureHomepageFoundation();

        if ($assetId !== null && $assetId > 0 && $this->mediaService->findAssetById($assetId) === null) {
            throw new \RuntimeException('Hero media asset not found.');
        }

        $heroBlock = $this->findBlockByKey('home.hero', false);

        if ($heroBlock === null) {
            throw new \RuntimeException('Homepage hero block not found.');
        }

        $this->app->database()->execute(
            'UPDATE content_blocks
             SET media_asset_id = :media_asset_id
             WHERE block_key = :block_key',
            [
                'media_asset_id' => $assetId !== null && $assetId > 0 ? $assetId : null,
                'block_key' => 'home.hero',
            ]
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function saveHomepageBlocks(array $payload): void
    {
        $definitions = $this->normalizeHomepagePayload($payload);

        foreach ($definitions as $definition) {
            $blockId = $this->upsertContentBlock($definition['block']);
            $this->replaceContentBlockItems($blockId, $definition['items']);
        }
    }

    public function ensureHomepageFoundation(): void
    {
        $existing = $this->app->database()->fetchAll(
            'SELECT block_key
             FROM content_blocks
             WHERE block_key IN (?, ?, ?, ?, ?, ?, ?)',
            self::HOMEPAGE_BLOCK_KEYS
        );

        $existingKeys = array_map(static fn (array $row): string => (string) ($row['block_key'] ?? ''), $existing);

        if (count(array_filter($existingKeys)) === count(self::HOMEPAGE_BLOCK_KEYS)) {
            return;
        }

        $this->saveHomepageBlocks([
            'hero_subheading' => 'Modern Florist Storefront',
            'hero_heading' => 'Meaningful bouquets for birthdays, sympathy, daily beauty, and same-day gifting.',
            'hero_body_text' => 'The homepage opens with richer florist-style storytelling while keeping the live catalog and checkout data fully intact.',
            'hero_cta_label' => 'Shop Best Sellers',
            'hero_cta_url' => '/best-sellers',
            'hero_media_asset_id' => '',
            'hero_is_enabled' => '1',
            'hero_sort_order' => '10',
            'hero_items_text' => "Browse Occasions|/occasions\nTalk To The Florist|/contact",
            'quick_links_subheading' => 'Quick Paths',
            'quick_links_heading' => 'Shortcuts shaped like a fuller florist homepage.',
            'quick_links_body_text' => 'These cards use current live routes where available and provide safe shells where backend features arrive later.',
            'quick_links_cta_label' => '',
            'quick_links_cta_url' => '',
            'quick_links_media_asset_id' => '',
            'quick_links_is_enabled' => '1',
            'quick_links_sort_order' => '20',
            'quick_links_items_text' => "Birthday|Occasion-led gifting ideas|/occasions\nSympathy|Comforting floral selections|/occasions\nBest Sellers|Top arrangements and fastest starts|/best-sellers\nFlowers|Signature bouquets and daily florals|/\nGifts + Food|UI shell for later add-on phases|/contact\nSame Day|Delivery rules and checkout path|/checkout",
            'feature_intro_subheading' => 'Featured Bouquets',
            'feature_intro_heading' => 'Live products with stronger card hierarchy.',
            'feature_intro_body_text' => 'The product grid still reads directly from the current product service. This phase only upgrades presentation, merchandising rhythm, and responsiveness.',
            'feature_intro_cta_label' => '',
            'feature_intro_cta_url' => '',
            'feature_intro_media_asset_id' => '',
            'feature_intro_is_enabled' => '1',
            'feature_intro_sort_order' => '30',
            'feature_intro_items_text' => "Mega-menu shells are live in the header.\nNewsletter and SEO sections are content-ready UI blocks.\nCatalog, cart, checkout, payment, and admin flows stay data-compatible.",
            'features_subheading' => 'Feature Cards',
            'features_heading' => 'Foundational storefront value props.',
            'features_body_text' => 'These cards keep the homepage merchandising and runtime positioning editable from admin.',
            'features_cta_label' => '',
            'features_cta_url' => '',
            'features_media_asset_id' => '',
            'features_is_enabled' => '1',
            'features_sort_order' => '40',
            'features_items_text' => "Locally Designed|Each arrangement is positioned as florist-made rather than generic catalog stock.\nCheckout Ready|Cart, checkout, payment placeholder, and confirmation already work with the existing runtime.\nExpandable Foundation|Search, account, promo, add-ons, and richer CMS blocks can layer in later phases.",
            'trust_subheading' => 'Trust Signals',
            'trust_heading' => 'Service confidence built into the storefront shell.',
            'trust_body_text' => 'These supporting cards stay content-managed while live delivery, gift message, and responsive runtime behavior remain intact.',
            'trust_cta_label' => '',
            'trust_cta_url' => '',
            'trust_media_asset_id' => '',
            'trust_is_enabled' => '1',
            'trust_sort_order' => '50',
            'trust_items_text' => "Trusted Local Delivery|Delivery windows and fees stay tied to the live order service and settings data.\nGift Message Ready|Card message and recipient details remain active in the current checkout flow.\nResponsive Experience|The layout renders safely on desktop and mobile without relying on inactive build output.",
            'newsletter_subheading' => 'Newsletter UI Block',
            'newsletter_heading' => 'Keep floral reminders and seasonal drops top of mind.',
            'newsletter_body_text' => 'This is a visual block only. Newsletter storage and delivery logic are intentionally deferred until backend phases.',
            'newsletter_cta_label' => 'Newsletter Placeholder',
            'newsletter_cta_url' => '',
            'newsletter_media_asset_id' => '',
            'newsletter_is_enabled' => '1',
            'newsletter_sort_order' => '60',
            'newsletter_items_text' => '',
            'seo_subheading' => 'SEO Content Block',
            'seo_heading' => 'Nashville flower delivery with a more editorial storefront voice.',
            'seo_body_text' => 'This section is ready for future CMS editing while the live store continues to rely on current routes and product data.',
            'seo_cta_label' => '',
            'seo_cta_url' => '',
            'seo_media_asset_id' => '',
            'seo_is_enabled' => '1',
            'seo_sort_order' => '70',
            'seo_items_text' => "Customers can already browse featured bouquets, shop by occasion, review serviceable delivery rules, and complete the existing order and payment placeholder flow.",
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getPublicPageBlocks(bool $enabledOnly = true): array
    {
        $this->ensurePublicPageFoundation();

        $blocks = [];

        foreach (self::PUBLIC_PAGE_BLOCK_KEYS as $blockKey) {
            $block = $this->findBlockByKey($blockKey, true);

            if ($block === null) {
                continue;
            }

            if ($enabledOnly && empty($block['is_enabled'])) {
                continue;
            }

            $blocks[$blockKey] = $block;
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function savePublicPageBlocks(array $payload): void
    {
        $definitions = $this->normalizePublicPagePayload($payload);

        foreach ($definitions as $definition) {
            $blockId = $this->upsertContentBlock($definition['block']);
            $this->replaceContentBlockItems($blockId, $definition['items']);
        }
    }

    public function ensurePublicPageFoundation(): void
    {
        $existing = $this->app->database()->fetchAll(
            'SELECT block_key
             FROM content_blocks
             WHERE block_key IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            self::PUBLIC_PAGE_BLOCK_KEYS
        );

        $existingKeys = array_map(static fn (array $row): string => (string) ($row['block_key'] ?? ''), $existing);

        if (count(array_filter($existingKeys)) === count(self::PUBLIC_PAGE_BLOCK_KEYS)) {
            return;
        }

        $this->savePublicPageBlocks([
            'contact_hero_subheading' => 'Contact The Florist',
            'contact_hero_heading' => 'Talk to the florist about deliveries, sympathy work, and custom flowers.',
            'contact_hero_body_text' => 'This remains a simple contact page with a clearer studio-style layout and the same underlying runtime.',
            'contact_hero_cta_label' => '',
            'contact_hero_cta_url' => '',
            'contact_hero_media_asset_id' => '',
            'contact_hero_is_enabled' => '1',
            'contact_hero_sort_order' => '10',
            'contact_hero_items_text' => '',
            'contact_support_subheading' => 'Current Support',
            'contact_support_heading' => 'When To Reach Out',
            'contact_support_body_text' => 'Phone, email, and same-day details still come from live store settings.',
            'contact_support_cta_label' => 'Review Delivery Rules',
            'contact_support_cta_url' => '/checkout',
            'contact_support_media_asset_id' => '',
            'contact_support_is_enabled' => '1',
            'contact_support_sort_order' => '20',
            'contact_support_items_text' => "Custom bouquet requests\nSympathy and memorial floral guidance\nDelivery timing questions\nPickup coordination and order help\nA fuller customer account, tracking, and content system can connect here later. For now, this page stays intentionally simple and safe.",
            'order_status_intro_subheading' => 'Order Tracking',
            'order_status_intro_heading' => 'Look up your order with the details from checkout.',
            'order_status_intro_body_text' => 'Enter the order number and customer email from checkout to view a customer-safe status summary.',
            'order_status_intro_cta_label' => 'Contact Store',
            'order_status_intro_cta_url' => '/contact',
            'order_status_intro_media_asset_id' => '',
            'order_status_intro_is_enabled' => '1',
            'order_status_intro_sort_order' => '30',
            'order_status_intro_items_text' => "Use the exact order number from your confirmation or notification.\nUse the same customer email entered at checkout.\nExisting tokenized payment and confirmation links still work separately.\nOrder number + email|Customer-safe tracking info|Internal-only order data",
            'order_status_empty_subheading' => 'Order Tracking',
            'order_status_empty_heading' => 'We could not match that order lookup.',
            'order_status_empty_body_text' => 'Check the order number and customer email from checkout, then try again.',
            'order_status_empty_cta_label' => '',
            'order_status_empty_cta_url' => '',
            'order_status_empty_media_asset_id' => '',
            'order_status_empty_is_enabled' => '1',
            'order_status_empty_sort_order' => '40',
            'order_status_empty_items_text' => '',
            'checkout_help_subheading' => 'Checkout',
            'checkout_help_heading' => 'Place your order with a calmer florist-style checkout.',
            'checkout_help_body_text' => 'All existing order, pricing, payment-placeholder, and notification logic stays intact, with add-ons carried through the live checkout flow.',
            'checkout_help_cta_label' => '',
            'checkout_help_cta_url' => '',
            'checkout_help_media_asset_id' => '',
            'checkout_help_is_enabled' => '1',
            'checkout_help_sort_order' => '50',
            'checkout_help_items_text' => "Delivery ZIP validation stays live.\nDelivery slots remain service-driven.\nAssigned product add-ons are included in pricing and order snapshots.\nOptional preset tips are added to the final order total only when selected.\nAll sales are final. Orders cannot be cancelled online, refunds are not guaranteed online, and you must contact the store directly for any issue with your order.",
            'payment_help_subheading' => 'Payment Placeholder',
            'payment_help_heading' => 'A clearer status screen for the existing placeholder payment flow.',
            'payment_help_body_text' => 'No real card gateway is connected here yet. The live flow still creates and updates placeholder payment records exactly as before, including saved add-on selections.',
            'payment_help_cta_label' => '',
            'payment_help_cta_url' => '',
            'payment_help_media_asset_id' => '',
            'payment_help_is_enabled' => '1',
            'payment_help_sort_order' => '60',
            'payment_help_items_text' => "This screen is redesigned only. The allowed simulation behavior and token-based access remain unchanged.\nYou can still simulate placeholder payment outcomes here. This is existing behavior with cleaner presentation only.",
            'order_confirmation_help_subheading' => 'Order Confirmation',
            'order_confirmation_help_heading' => 'A cleaner confirmation screen over the existing order flow.',
            'order_confirmation_help_body_text' => 'Your order has been saved locally, including any selected add-ons.',
            'order_confirmation_help_cta_label' => 'Track Another Order',
            'order_confirmation_help_cta_url' => '/order-status',
            'order_confirmation_help_media_asset_id' => '',
            'order_confirmation_help_is_enabled' => '1',
            'order_confirmation_help_sort_order' => '70',
            'order_confirmation_help_items_text' => "Continue Shopping|/\nThis remains the existing placeholder payment record for local QA. No external payment API call has been made.",
            'best_sellers_intro_subheading' => 'Best Sellers',
            'best_sellers_intro_heading' => 'Top bouquets with a stronger florist merchandising layout.',
            'best_sellers_intro_body_text' => 'Featured arrangements still come from the current product service. If no products are marked featured, the page continues to fall back to recent catalog items.',
            'best_sellers_intro_cta_label' => 'Browse Occasions',
            'best_sellers_intro_cta_url' => '/occasions',
            'best_sellers_intro_media_asset_id' => '',
            'best_sellers_intro_is_enabled' => '1',
            'best_sellers_intro_sort_order' => '80',
            'best_sellers_intro_items_text' => "Sorting and filtering arrive in later backend phases.\nThe visual hierarchy is upgraded now without changing runtime logic.\nGift Ready|Fast browsing for birthday, sympathy, romance, and everyday moments.\nSame-Day Friendly|Checkout already enforces the active delivery rules and cutoff window.\nClear Pricing|Cards are tuned to keep price and action hierarchy stronger on all screen sizes.",
            'occasions_intro_subheading' => 'Occasion Guide',
            'occasions_intro_heading' => 'Shop the live catalog by occasion with a cleaner editorial layout.',
            'occasions_intro_body_text' => 'This page keeps reading directly from the current occasion-to-product mappings while presenting a more polished browsing experience.',
            'occasions_intro_cta_label' => 'View Best Sellers',
            'occasions_intro_cta_url' => '/best-sellers',
            'occasions_intro_media_asset_id' => '',
            'occasions_intro_is_enabled' => '1',
            'occasions_intro_sort_order' => '90',
            'occasions_intro_items_text' => "Dedicated category landing pages, filters, and search will layer on top of this foundation.\nA richer category shell is in place here now. Sorting, filter chips, and SEO copy can be added in later phases without replacing this active route.",
            'search_intro_subheading' => 'Search Results',
            'search_intro_heading' => 'Search the live florist catalog using the current product, category, and occasion data.',
            'search_intro_body_text' => 'Use the search field below to browse bouquets, categories, and occasions.',
            'search_intro_cta_label' => '',
            'search_intro_cta_url' => '',
            'search_intro_media_asset_id' => '',
            'search_intro_is_enabled' => '1',
            'search_intro_sort_order' => '100',
            'search_intro_items_text' => '',
            'search_empty_subheading' => 'Search Results',
            'search_empty_heading' => 'No bouquets matched this search.',
            'search_empty_body_text' => 'Try a shorter phrase, a different category, or clear the featured-only filter.',
            'search_empty_cta_label' => '',
            'search_empty_cta_url' => '',
            'search_empty_media_asset_id' => '',
            'search_empty_is_enabled' => '1',
            'search_empty_sort_order' => '110',
            'search_empty_items_text' => '',
            'product_detail_helper_subheading' => 'Arrangement Notes',
            'product_detail_helper_heading' => 'Helpful notes before adding this arrangement to cart.',
            'product_detail_helper_body_text' => 'A refined florist product page with live pricing and size selection still driven by the current runtime.',
            'product_detail_helper_cta_label' => '',
            'product_detail_helper_cta_url' => '',
            'product_detail_helper_media_asset_id' => '',
            'product_detail_helper_is_enabled' => '1',
            'product_detail_helper_sort_order' => '120',
            'product_detail_helper_items_text' => "Choose optional extras below before adding this arrangement to cart.\nSelected add-ons will carry through cart, checkout, and the final order.\nNo add-ons are assigned to this arrangement right now.",
            'product_detail_related_subheading' => 'Related Arrangements',
            'product_detail_related_heading' => 'More bouquets chosen to complement this selection.',
            'product_detail_related_body_text' => 'This section uses manually assigned related products from the live admin product form.',
            'product_detail_related_cta_label' => '',
            'product_detail_related_cta_url' => '',
            'product_detail_related_media_asset_id' => '',
            'product_detail_related_is_enabled' => '1',
            'product_detail_related_sort_order' => '130',
            'product_detail_related_items_text' => "Suggested from the current live related-product mapping.",
        ]);
    }

    public function ensureGlobalChromeFoundation(): void
    {
        $this->ensurePromoBanner();
        $this->ensureFooterFoundation();
        $this->ensurePublicPageFoundation();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateBlock(array $row, bool $includeItems): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['media_asset_id'] = isset($row['media_asset_id']) ? (int) $row['media_asset_id'] : null;
        $row['is_enabled'] = !empty($row['is_enabled']);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['meta'] = $this->decodeJson($row['meta_json'] ?? null);
        $row['media_asset'] = $row['media_asset_id'] !== null
            ? $this->mediaService->findAssetById((int) $row['media_asset_id'])
            : null;

        if ($includeItems) {
            $row['items'] = $this->listBlockItems((int) $row['id'], true);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateBlockItem(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['content_block_id'] = (int) ($row['content_block_id'] ?? 0);
        $row['media_asset_id'] = isset($row['media_asset_id']) ? (int) $row['media_asset_id'] : null;
        $row['is_enabled'] = !empty($row['is_enabled']);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['meta'] = $this->decodeJson($row['meta_json'] ?? null);
        $row['media_asset'] = $row['media_asset_id'] !== null
            ? $this->mediaService->findAssetById((int) $row['media_asset_id'])
            : null;

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateBanner(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['media_asset_id'] = isset($row['media_asset_id']) ? (int) $row['media_asset_id'] : null;
        $row['is_enabled'] = !empty($row['is_enabled']);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['media_asset'] = $row['media_asset_id'] !== null
            ? $this->mediaService->findAssetById((int) $row['media_asset_id'])
            : null;

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeBannerPersistData(array $data): array
    {
        return [
            'banner_key' => trim((string) ($data['banner_key'] ?? '')),
            'page_key' => trim((string) ($data['page_key'] ?? 'global')),
            'placement' => trim((string) ($data['placement'] ?? 'promo_strip')),
            'title' => trim((string) ($data['title'] ?? '')),
            'subtitle' => trim((string) ($data['subtitle'] ?? '')),
            'body_text' => trim((string) ($data['body_text'] ?? '')),
            'cta_label' => trim((string) ($data['cta_label'] ?? '')),
            'cta_url' => trim((string) ($data['cta_url'] ?? '')),
            'media_asset_id' => !empty($data['media_asset_id']) ? (int) $data['media_asset_id'] : null,
            'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
    }

    private function ensurePromoBanner(): void
    {
        $countRow = $this->app->database()->query(
            'SELECT COUNT(*) AS total
             FROM banners
             WHERE banner_key = :banner_key',
            ['banner_key' => 'global-promo-strip']
        )->fetch();

        if ((int) ($countRow['total'] ?? 0) > 0) {
            return;
        }

        $this->createBanner([
            'banner_key' => 'global-promo-strip',
            'page_key' => 'global',
            'placement' => 'promo_strip',
            'title' => 'Hand-designed arrangements from a local florist',
            'subtitle' => 'Same-day delivery available before the posted cutoff.',
            'body_text' => 'Browse live bouquets, cart, checkout, and order tracking on the active runtime.',
            'cta_label' => 'Contact',
            'cta_url' => '/contact',
            'media_asset_id' => null,
            'is_enabled' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 10,
        ]);
    }

    private function ensureFooterFoundation(): void
    {
        $existing = $this->app->database()->fetchAll(
            'SELECT block_key
             FROM content_blocks
             WHERE block_key IN (?, ?, ?, ?, ?)',
            self::FOOTER_BLOCK_KEYS
        );

        $existingKeys = array_map(static fn (array $row): string => (string) ($row['block_key'] ?? ''), $existing);

        if (count(array_filter($existingKeys)) === count(self::FOOTER_BLOCK_KEYS)) {
            return;
        }

        $this->saveFooterBlocks([
            'about_heading' => 'Local Florist',
            'about_body_text' => 'Polished bouquets, sympathy florals, celebration arrangements, and same-day gifting designed for local delivery and pickup.',
            'about_items_text' => "Hand designed\nLocal delivery",
            'shop_heading' => 'Shop',
            'shop_items_text' => "Home|/\nBest Sellers|/best-sellers\nOccasions|/occasions\nCart|/cart\nCheckout|/checkout\nTrack Order|/order-status",
            'service_heading' => 'Service',
            'service_items_text' => "Contact|/contact\nOrder Tracking|/order-status\nSame-day cutoff: Posted at checkout\nPayment and order confirmation already active\nSearch and accounts arrive in later phases",
            'business_heading' => 'Business Info',
            'business_items_text' => "Phone coming soon\nEmail coming soon\nOrder online any time\nStore questions handled directly by the florist",
            'bottom_body_text' => 'Phase 1 UI foundation complete on the active PHP runtime.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{block: array<string, mixed>, items: array<int, array<string, mixed>>}>
     */
    private function normalizeFooterPayload(array $payload): array
    {
        return [
            [
                'block' => [
                    'block_key' => 'global.footer.about',
                    'page_key' => 'global',
                    'name' => 'Footer About',
                    'block_type' => 'footer_column',
                    'heading' => trim((string) ($payload['about_heading'] ?? '')),
                    'body_text' => trim((string) ($payload['about_body_text'] ?? '')),
                    'is_enabled' => 1,
                    'sort_order' => 10,
                ],
                'items' => $this->parseFooterItems((string) ($payload['about_items_text'] ?? ''), false),
            ],
            [
                'block' => [
                    'block_key' => 'global.footer.shop',
                    'page_key' => 'global',
                    'name' => 'Footer Shop',
                    'block_type' => 'footer_column',
                    'heading' => trim((string) ($payload['shop_heading'] ?? '')),
                    'body_text' => null,
                    'is_enabled' => 1,
                    'sort_order' => 20,
                ],
                'items' => $this->parseFooterItems((string) ($payload['shop_items_text'] ?? ''), true),
            ],
            [
                'block' => [
                    'block_key' => 'global.footer.service',
                    'page_key' => 'global',
                    'name' => 'Footer Service',
                    'block_type' => 'footer_column',
                    'heading' => trim((string) ($payload['service_heading'] ?? '')),
                    'body_text' => null,
                    'is_enabled' => 1,
                    'sort_order' => 30,
                ],
                'items' => $this->parseFooterItems((string) ($payload['service_items_text'] ?? ''), true),
            ],
            [
                'block' => [
                    'block_key' => 'global.footer.business',
                    'page_key' => 'global',
                    'name' => 'Footer Business',
                    'block_type' => 'footer_column',
                    'heading' => trim((string) ($payload['business_heading'] ?? '')),
                    'body_text' => null,
                    'is_enabled' => 1,
                    'sort_order' => 40,
                ],
                'items' => $this->parseFooterItems((string) ($payload['business_items_text'] ?? ''), true),
            ],
            [
                'block' => [
                    'block_key' => 'global.footer.bottom',
                    'page_key' => 'global',
                    'name' => 'Footer Bottom',
                    'block_type' => 'footer_meta',
                    'heading' => null,
                    'body_text' => trim((string) ($payload['bottom_body_text'] ?? '')),
                    'is_enabled' => 1,
                    'sort_order' => 50,
                ],
                'items' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{block: array<string, mixed>, items: array<int, array<string, mixed>>}>
     */
    private function normalizeHomepagePayload(array $payload): array
    {
        $blockMap = [
            'hero' => [
                'block_key' => 'home.hero',
                'name' => 'Homepage Hero',
                'block_type' => 'hero',
                'sort_order' => 10,
            ],
            'quick_links' => [
                'block_key' => 'home.quick-links',
                'name' => 'Homepage Quick Links',
                'block_type' => 'quick_links',
                'sort_order' => 20,
            ],
            'feature_intro' => [
                'block_key' => 'home.feature-intro',
                'name' => 'Homepage Feature Intro',
                'block_type' => 'feature_intro',
                'sort_order' => 30,
            ],
            'features' => [
                'block_key' => 'home.features',
                'name' => 'Homepage Features',
                'block_type' => 'feature_cards',
                'sort_order' => 40,
            ],
            'trust' => [
                'block_key' => 'home.trust',
                'name' => 'Homepage Trust',
                'block_type' => 'trust',
                'sort_order' => 50,
            ],
            'newsletter' => [
                'block_key' => 'home.newsletter',
                'name' => 'Homepage Newsletter',
                'block_type' => 'newsletter',
                'sort_order' => 60,
            ],
            'seo' => [
                'block_key' => 'home.seo',
                'name' => 'Homepage SEO',
                'block_type' => 'seo',
                'sort_order' => 70,
            ],
        ];

        $definitions = [];

        foreach ($blockMap as $prefix => $definition) {
            $definitions[] = [
                'block' => [
                    'block_key' => $definition['block_key'],
                    'page_key' => 'home',
                    'name' => $definition['name'],
                    'block_type' => $definition['block_type'],
                    'heading' => trim((string) ($payload[$prefix . '_heading'] ?? '')),
                    'subheading' => trim((string) ($payload[$prefix . '_subheading'] ?? '')),
                    'body_text' => trim((string) ($payload[$prefix . '_body_text'] ?? '')),
                    'cta_label' => trim((string) ($payload[$prefix . '_cta_label'] ?? '')),
                    'cta_url' => trim((string) ($payload[$prefix . '_cta_url'] ?? '')),
                    'media_asset_id' => !empty($payload[$prefix . '_media_asset_id']) ? (int) $payload[$prefix . '_media_asset_id'] : null,
                    'is_enabled' => !empty($payload[$prefix . '_is_enabled']) ? 1 : 0,
                    'sort_order' => max(0, (int) ($payload[$prefix . '_sort_order'] ?? $definition['sort_order'])),
                    'meta_json' => null,
                ],
                'items' => $this->parseHomepageItems((string) ($payload[$prefix . '_items_text'] ?? '')),
            ];
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{block: array<string, mixed>, items: array<int, array<string, mixed>>}>
     */
    private function normalizePublicPagePayload(array $payload): array
    {
        $blockMap = [
            'contact_hero' => ['block_key' => 'page.contact.hero', 'name' => 'Contact Hero', 'page_key' => 'contact', 'block_type' => 'page_intro', 'sort_order' => 10],
            'contact_support' => ['block_key' => 'page.contact.support', 'name' => 'Contact Support', 'page_key' => 'contact', 'block_type' => 'page_support', 'sort_order' => 20],
            'order_status_intro' => ['block_key' => 'page.order-status.intro', 'name' => 'Order Status Intro', 'page_key' => 'order-status', 'block_type' => 'page_intro', 'sort_order' => 30],
            'order_status_empty' => ['block_key' => 'page.order-status.empty', 'name' => 'Order Status Empty', 'page_key' => 'order-status', 'block_type' => 'page_empty', 'sort_order' => 40],
            'checkout_help' => ['block_key' => 'page.checkout.help', 'name' => 'Checkout Help', 'page_key' => 'checkout', 'block_type' => 'page_help', 'sort_order' => 50],
            'payment_help' => ['block_key' => 'page.payment.help', 'name' => 'Payment Help', 'page_key' => 'payment', 'block_type' => 'page_help', 'sort_order' => 60],
            'order_confirmation_help' => ['block_key' => 'page.order-confirmation.help', 'name' => 'Order Confirmation Help', 'page_key' => 'order-confirmation', 'block_type' => 'page_help', 'sort_order' => 70],
            'best_sellers_intro' => ['block_key' => 'page.best-sellers.intro', 'name' => 'Best Sellers Intro', 'page_key' => 'best-sellers', 'block_type' => 'page_intro', 'sort_order' => 80],
            'occasions_intro' => ['block_key' => 'page.occasions.intro', 'name' => 'Occasions Intro', 'page_key' => 'occasions', 'block_type' => 'page_intro', 'sort_order' => 90],
            'search_intro' => ['block_key' => 'page.search.intro', 'name' => 'Search Intro', 'page_key' => 'search', 'block_type' => 'page_intro', 'sort_order' => 100],
            'search_empty' => ['block_key' => 'page.search.empty', 'name' => 'Search Empty', 'page_key' => 'search', 'block_type' => 'page_empty', 'sort_order' => 110],
            'product_detail_helper' => ['block_key' => 'page.product-detail.helper', 'name' => 'Product Detail Helper', 'page_key' => 'product-detail', 'block_type' => 'page_helper', 'sort_order' => 120],
            'product_detail_related' => ['block_key' => 'page.product-detail.related', 'name' => 'Product Detail Related', 'page_key' => 'product-detail', 'block_type' => 'page_related', 'sort_order' => 130],
        ];

        $definitions = [];

        foreach ($blockMap as $prefix => $definition) {
            $definitions[] = [
                'block' => [
                    'block_key' => $definition['block_key'],
                    'page_key' => $definition['page_key'],
                    'name' => $definition['name'],
                    'block_type' => $definition['block_type'],
                    'heading' => trim((string) ($payload[$prefix . '_heading'] ?? '')),
                    'subheading' => trim((string) ($payload[$prefix . '_subheading'] ?? '')),
                    'body_text' => trim((string) ($payload[$prefix . '_body_text'] ?? '')),
                    'cta_label' => trim((string) ($payload[$prefix . '_cta_label'] ?? '')),
                    'cta_url' => trim((string) ($payload[$prefix . '_cta_url'] ?? '')),
                    'media_asset_id' => !empty($payload[$prefix . '_media_asset_id']) ? (int) $payload[$prefix . '_media_asset_id'] : null,
                    'is_enabled' => !empty($payload[$prefix . '_is_enabled']) ? 1 : 0,
                    'sort_order' => max(0, (int) ($payload[$prefix . '_sort_order'] ?? $definition['sort_order'])),
                    'meta_json' => null,
                ],
                'items' => $this->parseHomepageItems((string) ($payload[$prefix . '_items_text'] ?? '')),
            ];
        }

        return $definitions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFooterItems(string $value, bool $allowLinks): array
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $sortOrder = 10;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $title = $line;
            $ctaUrl = null;

            if ($allowLinks && str_contains($line, '|')) {
                [$titlePart, $urlPart] = array_pad(explode('|', $line, 2), 2, '');
                $title = trim($titlePart);
                $ctaUrl = trim($urlPart) !== '' ? trim($urlPart) : null;
            }

            $items[] = [
                'title' => $title,
                'cta_url' => $ctaUrl,
                'sort_order' => $sortOrder,
                'is_enabled' => 1,
            ];
            $sortOrder += 10;
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseHomepageItems(string $value): array
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $sortOrder = 10;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $title = $parts[0] ?? '';
            $bodyText = null;
            $ctaUrl = null;

            if (count($parts) >= 3) {
                $bodyText = $parts[1] !== '' ? $parts[1] : null;
                $ctaUrl = $parts[2] !== '' ? $parts[2] : null;
            } elseif (count($parts) === 2) {
                if (str_starts_with($parts[1], '/') || filter_var($parts[1], FILTER_VALIDATE_URL) !== false) {
                    $ctaUrl = $parts[1] !== '' ? $parts[1] : null;
                } else {
                    $bodyText = $parts[1] !== '' ? $parts[1] : null;
                }
            }

            if ($title === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'body_text' => $bodyText,
                'cta_url' => $ctaUrl,
                'sort_order' => $sortOrder,
                'is_enabled' => 1,
            ];

            $sortOrder += 10;
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function upsertContentBlock(array $block): int
    {
        $existing = $this->findBlockByKey((string) ($block['block_key'] ?? ''), false);
        $metaValue = $block['meta_json'] ?? null;
        $metaJson = $metaValue !== null ? json_encode($metaValue) : null;

        if ($existing === null) {
            $this->app->database()->execute(
                'INSERT INTO content_blocks (
                    block_key,
                    page_key,
                    name,
                    block_type,
                    heading,
                    subheading,
                    body_text,
                    cta_label,
                    cta_url,
                    media_asset_id,
                    is_enabled,
                    sort_order,
                    meta_json
                 ) VALUES (
                    :block_key,
                    :page_key,
                    :name,
                    :block_type,
                    :heading,
                    :subheading,
                    :body_text,
                    :cta_label,
                    :cta_url,
                    :media_asset_id,
                    :is_enabled,
                    :sort_order,
                    :meta_json
                 )',
                [
                    'block_key' => (string) $block['block_key'],
                    'page_key' => (string) $block['page_key'],
                    'name' => (string) $block['name'],
                    'block_type' => (string) $block['block_type'],
                    'heading' => $block['heading'],
                    'subheading' => $block['subheading'] ?? null,
                    'body_text' => $block['body_text'],
                    'cta_label' => $block['cta_label'] ?? null,
                    'cta_url' => $block['cta_url'] ?? null,
                    'media_asset_id' => $block['media_asset_id'] ?? null,
                    'is_enabled' => (int) $block['is_enabled'],
                    'sort_order' => (int) $block['sort_order'],
                    'meta_json' => $metaJson,
                ]
            );

            return (int) $this->app->database()->connection()->lastInsertId();
        }

        $this->app->database()->execute(
            'UPDATE content_blocks
             SET page_key = :page_key,
                 name = :name,
                 block_type = :block_type,
                 heading = :heading,
                 subheading = :subheading,
                 body_text = :body_text,
                 cta_label = :cta_label,
                 cta_url = :cta_url,
                 media_asset_id = :media_asset_id,
                 is_enabled = :is_enabled,
                 sort_order = :sort_order,
                 meta_json = :meta_json
             WHERE id = :id',
            [
                'id' => (int) $existing['id'],
                'page_key' => (string) $block['page_key'],
                'name' => (string) $block['name'],
                'block_type' => (string) $block['block_type'],
                'heading' => $block['heading'],
                'subheading' => $block['subheading'] ?? null,
                'body_text' => $block['body_text'],
                'cta_label' => $block['cta_label'] ?? null,
                'cta_url' => $block['cta_url'] ?? null,
                'media_asset_id' => $block['media_asset_id'] ?? null,
                'is_enabled' => (int) $block['is_enabled'],
                'sort_order' => (int) $block['sort_order'],
                'meta_json' => $metaJson,
            ]
        );

        return (int) $existing['id'];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function replaceContentBlockItems(int $contentBlockId, array $items): void
    {
        $this->app->database()->execute(
            'DELETE FROM content_block_items
             WHERE content_block_id = :content_block_id',
            ['content_block_id' => $contentBlockId]
        );

        foreach ($items as $item) {
            $this->app->database()->execute(
                'INSERT INTO content_block_items (
                    content_block_id,
                    item_key,
                    title,
                    subtitle,
                    body_text,
                    cta_label,
                    cta_url,
                    media_asset_id,
                    is_enabled,
                    sort_order,
                    meta_json
                 ) VALUES (
                    :content_block_id,
                    :item_key,
                    :title,
                    :subtitle,
                    :body_text,
                    :cta_label,
                    :cta_url,
                    :media_asset_id,
                    :is_enabled,
                    :sort_order,
                    :meta_json
                 )',
                [
                    'content_block_id' => $contentBlockId,
                    'item_key' => $item['item_key'] ?? null,
                    'title' => (string) ($item['title'] ?? ''),
                    'subtitle' => $item['subtitle'] ?? null,
                    'body_text' => $item['body_text'] ?? null,
                    'cta_label' => $item['cta_label'] ?? null,
                    'cta_url' => $item['cta_url'] ?? null,
                    'media_asset_id' => $item['media_asset_id'] ?? null,
                    'is_enabled' => !empty($item['is_enabled']) ? 1 : 0,
                    'sort_order' => max(0, (int) ($item['sort_order'] ?? 0)),
                    'meta_json' => !empty($item['meta_json']) ? json_encode($item['meta_json']) : null,
                ]
            );
        }
    }
}
