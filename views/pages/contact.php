<?php
$publicBlocks = public_page_blocks(true);
$contactHero = $publicBlocks['page.contact.hero'] ?? [
    'subheading' => 'CONTACT',
    'heading' => 'GET IN TOUCH',
    'body_text' => 'We are here to help with orders, deliveries, and store questions.',
];
$contactSupport = $publicBlocks['page.contact.support'] ?? [
    'subheading' => 'SUPPORT',
    'heading' => 'HOW CAN WE HELP?',
    'body_text' => 'Our team is available to assist you with orders, deliveries, and bespoke requests.',
    'cta_label' => 'TRACK ORDER',
    'cta_url' => '/order-status',
    'items' => [
        ['title' => 'Bespoke event and corporate requests'],
        ['title' => 'Sympathy and memorial arrangements'],
        ['title' => 'Delivery tracking and order updates'],
        ['title' => 'General product inquiries'],
        ['title' => 'Please have your order number ready when contacting us regarding an existing purchase.'],
    ],
];
$contactSupportItems = is_array($contactSupport['items'] ?? null) ? $contactSupport['items'] : [];
?>
<main class="page-contact" style="margin-top:0;background:var(--color-white);min-height:80vh;">
    <!-- HEADER -->
    <div style="padding:4rem 1rem;text-align:center;border-bottom:1px solid var(--color-gray-light);background:var(--color-off-white);margin-bottom:3rem;">
        <div class="container">
            <h1 style="font-family:var(--font-heading);color:var(--color-black);font-size:3rem;font-weight:500;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.15em;">
                <?php echo htmlspecialchars((string) ($contactHero['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p style="color:var(--color-gray-dark);font-size:0.9rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 auto;">
                <?php echo htmlspecialchars((string) ($contactHero['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <?php if (!empty($contactHero['body_text'])): ?>
                <p style="color:var(--color-gray-dark);font-size:0.95rem;max-width:42rem;margin:1rem auto 0;line-height:1.7;text-transform:none;letter-spacing:normal;">
                    <?php echo htmlspecialchars((string) ($contactHero['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container mb-5">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:2rem;margin-bottom:4rem;">
            <div style="background:var(--color-white);padding:3rem 2rem;border:1px solid var(--color-gray-light);text-align:center;">
                <p style="font-family:var(--font-heading);font-size:0.85rem;text-transform:uppercase;color:var(--color-black);margin-bottom:1rem;letter-spacing:0.1em;font-weight:600;">VISIT US</p>
                <strong style="display:block;font-size:1.1rem;color:var(--color-gray-dark);font-weight:400;"><?php echo htmlspecialchars((string) settings('store_name', 'Lily and Rose'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div style="background:var(--color-white);padding:3rem 2rem;border:1px solid var(--color-gray-light);text-align:center;">
                <p style="font-family:var(--font-heading);font-size:0.85rem;text-transform:uppercase;color:var(--color-black);margin-bottom:1rem;letter-spacing:0.1em;font-weight:600;">CALL US</p>
                <strong style="display:block;font-size:1.1rem;color:var(--color-gray-dark);font-weight:400;"><?php echo htmlspecialchars((string) settings('store_phone', 'Phone coming soon'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div style="background:var(--color-white);padding:3rem 2rem;border:1px solid var(--color-gray-light);text-align:center;">
                <p style="font-family:var(--font-heading);font-size:0.85rem;text-transform:uppercase;color:var(--color-black);margin-bottom:1rem;letter-spacing:0.1em;font-weight:600;">EMAIL US</p>
                <strong style="display:block;font-size:1.1rem;color:var(--color-black);font-weight:400;text-decoration:underline;"><?php echo htmlspecialchars((string) settings('store_email', 'Email coming soon'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:start;">
            <div style="background:var(--color-white);padding:3rem;border:1px solid var(--color-gray-light);">
                <p class="eyebrow" style="color:var(--color-gray-dark);margin-bottom:1rem;"><?php echo htmlspecialchars((string) ($contactSupport['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <h2 style="font-family:var(--font-heading);color:var(--color-black);font-size:2rem;font-weight:500;text-transform:uppercase;margin-bottom:1.5rem;letter-spacing:0.1em;"><?php echo htmlspecialchars((string) ($contactSupport['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p style="color:var(--color-gray-dark);line-height:1.6;margin-bottom:2rem;font-size:0.85rem;"><?php echo htmlspecialchars((string) ($contactSupport['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                
                <ul style="list-style:none;padding:0;margin:0 0 2.5rem 0;display:flex;flex-direction:column;gap:1.25rem;">
                    <?php foreach (array_slice($contactSupportItems, 0, 4) as $item): ?>
                        <li style="display:flex;align-items:flex-start;gap:1rem;color:var(--color-black);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">
                            <span style="display:inline-block;width:4px;height:4px;border-radius:50%;background:var(--color-black);margin-top:0.4rem;"></span>
                            <?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($contactSupport['cta_label']) && !empty($contactSupport['cta_url']) && (string) ($contactSupport['cta_url'] ?? '') !== '/#'): ?>
                    <a href="<?php echo htmlspecialchars((string) ($contactSupport['cta_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-secondary"><?php echo htmlspecialchars((string) ($contactSupport['cta_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>

            <div style="background:var(--color-off-white);padding:3rem;border:1px solid var(--color-gray-light);">
                <p class="eyebrow" style="color:var(--color-black);margin-bottom:1rem;font-weight:600;">IMPORTANT INFORMATION</p>
                <p style="color:var(--color-gray-dark);line-height:1.6;margin:0;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;"><?php echo htmlspecialchars((string) (($contactSupportItems[4]['title'] ?? 'Please have your order number ready when contacting us regarding an existing purchase.')), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
</main>
