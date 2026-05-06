<?php
    $items = is_array($items ?? null) ? $items : [];
    $primaryItem = $items[0] ?? [];
    $store = is_array($store ?? null) ? $store : [];
    $cardText = is_array($cardText ?? null) ? $cardText : [];

    $text = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $plain = static fn (mixed $value): string => trim((string) ($value ?? ''));

    $storeName = $plain(($cardText['print_card_brand_display_name'] ?? '') ?: ($store['name'] ?? 'Lily and Rose'));
    $brandSubtitle = $plain($cardText['print_card_brand_subtitle'] ?? '');
    $frontKicker = $plain($cardText['print_card_front_kicker'] ?? 'Gift Message');
    $centerHeading = $plain($cardText['print_card_center_heading'] ?? 'A note for you');
    $emptyMessageFallback = $plain($cardText['print_card_empty_message_fallback'] ?? 'No card message was provided for this order.');
    $detailsHeading = $plain($cardText['print_card_details_heading'] ?? 'Delivery Details');
    $labelProduct = $plain($cardText['print_card_label_product'] ?? 'Flowers');
    $labelSize = $plain($cardText['print_card_label_size'] ?? 'Size');
    $labelRecipient = $plain($cardText['print_card_label_recipient'] ?? 'Recipient');
    $labelDeliveryDate = $plain($cardText['print_card_label_delivery_date'] ?? 'Delivery Date');
    $labelStoreContact = $plain($cardText['print_card_label_store_contact'] ?? 'Store Contact');
    $storeInitials = implode('', array_map(static fn (string $part): string => strtoupper(substr($part, 0, 1)), array_slice(array_filter(explode(' ', $storeName)), 0, 3)));
    $cardMessage = $plain($order['card_message'] ?? '');
    $hasCardMessage = $cardMessage !== '';
    $messageLengthClass = strlen($cardMessage) > 420 ? ' is-long' : (strlen($cardMessage) > 220 ? ' is-medium' : '');
    $productName = $plain($primaryItem['product_name'] ?? '');
    $variantName = $plain($primaryItem['variant_name'] ?? '');
    $recipientName = $plain($order['recipient_name'] ?? '');
    $deliveryDate = $plain($order['delivery_date'] ?? '');
    $orderNumber = $plain($order['order_number'] ?? '');
    $autoPrint = (bool) ($autoPrint ?? true);
    $hideToolbar = (bool) ($hideToolbar ?? false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $text($pageTitle ?? 'Print Card Note'); ?></title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; background: #eef1f5; color: #172033; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .print-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; max-width: 1180px; margin: 1rem auto; padding: 0.8rem 1rem; border: 1px solid #dbe3ee; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(15,23,42,0.08); }
        .print-toolbar p { margin: 0; color: #667085; font-size: 0.92rem; }
        .print-toolbar strong { display: block; color: #0f172a; font-size: 1rem; }
        .print-actions { display: flex; gap: 0.65rem; flex-wrap: wrap; }
        .print-button { border: 1px solid #111827; border-radius: 999px; padding: 0.7rem 1.05rem; color: #fff; background: #111827; cursor: pointer; font-weight: 700; text-decoration: none; }
        .print-button.secondary { color: #111827; background: #fff; border-color: #d5dde8; }
        .sheet-wrap { padding: 1rem; }
        .tri-fold-sheet { position: relative; width: 297mm; height: 210mm; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); background: #fffdfb; border: 1px solid #e7dde0; box-shadow: 0 24px 70px rgba(15,23,42,0.16); overflow: hidden; }
        .tri-fold-sheet::before,
        .tri-fold-sheet::after { content: ""; position: absolute; top: 13mm; bottom: 13mm; width: 0; border-left: 1px dashed rgba(111, 87, 95, 0.16); pointer-events: none; z-index: 2; }
        .tri-fold-sheet::before { left: 33.333%; }
        .tri-fold-sheet::after { left: 66.666%; }
        .note-panel { position: relative; min-width: 0; padding: 18mm 15mm; display: grid; align-content: center; justify-items: center; text-align: center; overflow: hidden; }
        .note-panel + .note-panel { border-left: 1px solid #f4ecee; }
        .note-panel--brand { background: linear-gradient(180deg, #fff 0%, #fffaf6 100%); }
        .note-panel--message { background: radial-gradient(circle at center, rgba(250,244,238,0.84) 0%, #fffefc 58%, #fff 100%); }
        .note-panel--info { background: linear-gradient(180deg, #fdfcf9 0%, #fff 100%); text-align: left; justify-items: stretch; }
        .brand-mark { width: 24mm; height: 24mm; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 9mm; background: #172033; color: #fff; font-family: Georgia, "Times New Roman", serif; font-size: 8mm; letter-spacing: 0.08em; box-shadow: 0 4mm 12mm rgba(23,32,51,0.08); }
        .brand-name { margin: 0; font-family: Georgia, "Times New Roman", serif; font-size: 13mm; font-weight: 400; line-height: 1.04; color: #111827; }
        .brand-support { margin: 4mm 0 0; color: #5d6472; font-size: 3.55mm; line-height: 1.35; }
        .brand-subtitle { margin: 7mm 0 0; color: #8f6b54; text-transform: uppercase; letter-spacing: 0.22em; font-size: 3.15mm; font-weight: 700; }
        .brand-line { width: 26mm; height: 1px; margin: 8mm auto 0; background: #decfba; }
        .message-label { margin: 0 0 8mm; color: #8f6b54; text-transform: uppercase; letter-spacing: 0.2em; font-size: 3mm; font-weight: 700; }
        .message-label::after { content: ""; display: block; width: 18mm; height: 1px; margin: 5mm auto 0; background: #decfba; }
        .message-text { margin: 0; max-width: 72mm; color: #101827; font-family: Georgia, "Times New Roman", serif; font-size: 9.4mm; line-height: 1.42; white-space: pre-wrap; overflow-wrap: anywhere; text-wrap: pretty; }
        .message-text.is-medium { font-size: 7mm; line-height: 1.34; }
        .message-text.is-long { font-size: 5.2mm; line-height: 1.3; text-align: left; }
        .message-fallback { margin: 0; max-width: 64mm; color: #7b8798; font-size: 5mm; line-height: 1.45; }
        .info-content { display: grid; align-content: center; gap: 4.2mm; width: 100%; min-height: 100%; }
        .info-title { margin: 0 0 1mm; font-family: Georgia, "Times New Roman", serif; font-size: 7.4mm; font-weight: 400; color: #172033; text-align: center; }
        .info-title::after { content: ""; display: block; width: 20mm; height: 1px; margin: 4mm auto 0; background: #decfba; }
        .info-list { display: grid; gap: 2.5mm; }
        .info-row { padding: 3mm 0; border-bottom: 1px solid #f0e9dd; }
        .info-row:first-child { border-top: 1px solid #f0e9dd; }
        .info-row span { display: block; margin-bottom: 1mm; color: #8f6b54; text-transform: uppercase; letter-spacing: 0.13em; font-size: 2.55mm; font-weight: 700; }
        .info-row strong { display: block; color: #293241; font-size: 3.85mm; line-height: 1.3; overflow-wrap: anywhere; font-weight: 600; }
        .store-reference { margin-top: 1mm; padding-top: 4mm; border-top: 1px solid #decfba; color: #5d6472; font-size: 3.25mm; line-height: 1.45; text-align: center; }
        .store-reference span { display: block; margin-bottom: 1.5mm; color: #8f6b54; text-transform: uppercase; letter-spacing: 0.13em; font-size: 2.45mm; font-weight: 700; }
        .store-reference strong { display: block; color: #172033; font-family: Georgia, "Times New Roman", serif; font-size: 4.7mm; font-weight: 400; margin-bottom: 1.5mm; }
        @media print {
            html, body { width: 297mm; height: 210mm; background: #fff; }
            .print-toolbar { display: none !important; }
            .sheet-wrap { padding: 0; }
            .tri-fold-sheet { width: 297mm; height: 210mm; border: 0; box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <?php if (!$hideToolbar): ?>
        <div class="print-toolbar">
            <p><strong>Card Note Preview</strong>A4 landscape tri-fold for <?php echo $text($orderNumber); ?></p>
            <div class="print-actions">
                <a class="print-button secondary" href="/admin/orders/view?id=<?php echo urlencode((string) ($order['id'] ?? '')); ?>">Back to Order</a>
                <button class="print-button" type="button" onclick="window.print()">Print Card Note</button>
            </div>
        </div>
    <?php endif; ?>

    <main class="sheet-wrap">
        <section class="tri-fold-sheet" aria-label="A4 landscape tri-fold card note">
            <article class="note-panel note-panel--brand">
                <div>
                    <div class="brand-mark"><?php echo $text($storeInitials !== '' ? $storeInitials : 'LR'); ?></div>
                    <h1 class="brand-name"><?php echo $text($storeName !== '' ? $storeName : 'Lily and Rose'); ?></h1>
                    <?php if ($brandSubtitle !== ''): ?>
                        <p class="brand-support"><?php echo $text($brandSubtitle); ?></p>
                    <?php endif; ?>
                    <p class="brand-subtitle"><?php echo $text($frontKicker !== '' ? $frontKicker : 'Gift Message'); ?></p>
                    <div class="brand-line"></div>
                </div>
            </article>

            <article class="note-panel note-panel--message">
                <div>
                    <p class="message-label"><?php echo $text($centerHeading !== '' ? $centerHeading : 'A note for you'); ?></p>
                    <?php if ($hasCardMessage): ?>
                        <p class="message-text<?php echo $text($messageLengthClass); ?>"><?php echo $text($cardMessage); ?></p>
                    <?php else: ?>
                        <p class="message-fallback"><?php echo $text($emptyMessageFallback !== '' ? $emptyMessageFallback : 'No card message was provided for this order.'); ?></p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="note-panel note-panel--info">
                <div class="info-content">
                    <h2 class="info-title"><?php echo $text($detailsHeading !== '' ? $detailsHeading : 'Delivery Details'); ?></h2>
                    <div class="info-list">
                        <?php if ($productName !== ''): ?>
                            <div class="info-row"><span><?php echo $text($labelProduct !== '' ? $labelProduct : 'Flowers'); ?></span><strong><?php echo $text($productName); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($variantName !== ''): ?>
                            <div class="info-row"><span><?php echo $text($labelSize !== '' ? $labelSize : 'Size'); ?></span><strong><?php echo $text($variantName); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($recipientName !== ''): ?>
                            <div class="info-row"><span><?php echo $text($labelRecipient !== '' ? $labelRecipient : 'Recipient'); ?></span><strong><?php echo $text($recipientName); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($deliveryDate !== ''): ?>
                            <div class="info-row"><span><?php echo $text($labelDeliveryDate !== '' ? $labelDeliveryDate : 'Delivery Date'); ?></span><strong><?php echo $text($deliveryDate); ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <div class="store-reference">
                        <?php if ($labelStoreContact !== ''): ?>
                            <span><?php echo $text($labelStoreContact); ?></span>
                        <?php endif; ?>
                        <strong><?php echo $text($storeName !== '' ? $storeName : 'Lily and Rose'); ?></strong>
                        <?php if (!empty($store['address'])): ?>
                            <?php echo nl2br($text($store['address'])); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($store['phone'])): ?>
                            <?php echo $text($store['phone']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($store['email'])): ?>
                            <?php echo $text($store['email']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </section>
    </main>

    <?php if ($autoPrint): ?>
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 350);
            });
        </script>
    <?php endif; ?>
</body>
</html>
