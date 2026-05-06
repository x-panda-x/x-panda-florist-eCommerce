<?php
$customer = is_array($customer ?? null) ? $customer : [];
$order = is_array($order ?? null) ? $order : null;
$items = is_array($items ?? null) ? $items : [];
$payment = is_array($payment ?? null) ? $payment : null;
$publicTracking = is_array($publicTracking ?? null) ? $publicTracking : null;
?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <?php if ($order === null): ?>
        <div class="empty-state">
            <p class="eyebrow" style="margin-bottom:0.5rem;">Order Detail</p>
            <h3 class="serif-head" style="margin-bottom:1rem;font-size:1.5rem;">Order Not Found</h3>
            <p class="site-note" style="margin-bottom:1.5rem;">That order could not be found for the current customer account.</p>
            <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin-top:1.5rem;">
                <a href="/account/orders" class="btn">Back To Orders</a>
                <a href="/account" class="btn-secondary">Back To Account</a>
            </div>
        </div>
    <?php else: ?>
        <div class="stack-xl">
            <div class="account-card" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:2rem;">
                <div>
                    <h2 class="serif-head" style="font-size:1.6rem;margin:0;font-family:var(--font-heading);letter-spacing:0.05em;color:var(--color-black);"><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="site-note" style="margin-top:0.5rem;margin-bottom:0;">Placed on <?php echo htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <a href="/account/orders" class="btn-secondary" style="padding:0.6rem 1.25rem;">Back To Orders</a>
                    <a href="/order-status" class="btn" style="padding:0.6rem 1.25rem;">Public Tracking Page</a>
                </div>
            </div>

            <div class="commerce-grid">
                <div class="stack-lg">
                    <div class="account-card">
                        <div class="account-card-header">
                            <h3 class="serif-head" style="font-size:1.25rem;margin:0;">Order Snapshot</h3>
                        </div>
                        <div class="detail-columns" style="margin-top:1rem;">
                            <div class="detail-card">
                                <p class="summary-label">Customer</p>
                                <p class="site-note" style="margin:0;"><?php echo htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="detail-card">
                                <p class="summary-label">Recipient</p>
                                <p class="site-note" style="margin:0;"><?php echo htmlspecialchars((string) ($order['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="detail-card">
                                <p class="summary-label">Delivery ZIP</p>
                                <p class="site-note" style="margin:0;"><?php echo htmlspecialchars((string) ($order['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="detail-card">
                                <p class="summary-label">Payment Status</p>
                                <p class="site-note" style="margin:0;"><?php echo htmlspecialchars((string) ($payment['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        
                        <div class="detail-card" style="margin-top:1rem;">
                            <p class="summary-label">Delivery Address</p>
                            <p class="site-note" style="margin:0;white-space:pre-line;"><?php echo htmlspecialchars((string) ($order['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <?php if (!empty($order['delivery_instructions'])): ?>
                            <div class="detail-card" style="margin-top:1rem;">
                                <p class="summary-label">Delivery Instructions</p>
                                <p class="site-note" style="margin:0;white-space:pre-line;"><?php echo htmlspecialchars((string) ($order['delivery_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($order['card_message'])): ?>
                            <div class="detail-card" style="margin-top:1rem;">
                                <p class="summary-label">Card Message</p>
                                <p class="site-note" style="margin:0;white-space:pre-line;"><?php echo htmlspecialchars((string) ($order['card_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($publicTracking['note'])): ?>
                            <div class="detail-card" style="margin-top:1rem;">
                                <p class="summary-label">Tracking Note</p>
                                <p class="site-note" style="margin:0;white-space:pre-line;"><?php echo htmlspecialchars((string) ($publicTracking['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="account-card">
                        <div class="account-card-header">
                            <h3 class="serif-head" style="font-size:1.25rem;margin:0;">Items</h3>
                        </div>
                        <?php if ($items === []): ?>
                            <p class="site-note">No order items were found for this order.</p>
                        <?php else: ?>
                            <div class="stack-md" style="margin-top:1rem;">
                                <?php foreach ($items as $item): ?>
                                    <div class="detail-card">
                                        <div class="summary-row" style="margin:0;border:none;padding:0;">
                                            <div>
                                                <strong style="font-family:var(--font-heading);font-size:1.1rem;color:var(--color-black);"><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <?php if (!empty($item['variant_name'])): ?>
                                                    <div class="site-note" style="color:var(--color-gray-dark);"><?php echo htmlspecialchars((string) ($item['variant_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                                <div class="site-note">Qty <?php echo htmlspecialchars((string) ($item['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php foreach (($item['addons'] ?? []) as $addon): ?>
                                                    <div class="site-note">
                                                        Add-on: <?php echo htmlspecialchars((string) ($addon['addon_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                        x<?php echo htmlspecialchars((string) ($addon['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>
                                                        ($<?php echo htmlspecialchars(number_format((float) ($addon['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?>)
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <strong style="font-size:1.1rem;color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="stack-lg">
                    <div class="account-card">
                        <p class="eyebrow" style="margin-bottom:1rem;">Tracking Status</p>
                        <div class="stack-sm">
                            <span class="site-note"><strong>Status:</strong> <?php echo htmlspecialchars((string) (($publicTracking['label'] ?? '') !== '' ? $publicTracking['label'] : ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($publicTracking['updated_at'])): ?>
                                <span class="site-note"><strong>Updated:</strong> <?php echo htmlspecialchars((string) ($publicTracking['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <span class="site-note"><strong>Delivery:</strong> <?php echo htmlspecialchars((string) ($order['delivery_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($order['delivery_time_slot'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <div class="account-card summary-card" style="padding:1.5rem;">
                        <p class="eyebrow">Totals</p>
                        <div class="summary-list" style="margin-top:1rem;">
                            <div class="summary-row"><span>Subtotal</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['subtotal'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php if (!empty($order['promo_code']) && (float) ($order['promo_discount_amount'] ?? 0) > 0): ?>
                                <div class="summary-row" style="color:var(--color-rose);"><span>Promo (<?php echo htmlspecialchars((string) ($order['promo_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</span><strong>-$<?php echo htmlspecialchars(number_format((float) ($order['promo_discount_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php endif; ?>
                            <div class="summary-row"><span>Delivery Fee</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['delivery_fee'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="summary-row"><span>Tax</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['tax_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php if ((float) ($order['tip_amount'] ?? 0) > 0): ?>
                                <div class="summary-row"><span>Tip</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['tip_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php endif; ?>
                            <div class="summary-row summary-total" style="font-size:1.25rem;"><span>Total</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        </div>
                    </div>

                    <div class="account-card">
                        <p class="eyebrow" style="margin-bottom:1rem;">Payment Snapshot</p>
                        <?php if (empty($payment)): ?>
                            <p class="site-note">No payment record is linked to this order yet.</p>
                        <?php else: ?>
                            <div class="detail-list">
                                <div class="detail-row"><span>Reference</span><strong><?php echo htmlspecialchars((string) ($payment['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div class="detail-row"><span>Status</span><span class="status-pill"><?php echo htmlspecialchars((string) ($payment['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                <div class="detail-row"><span>Provider</span><strong><?php echo htmlspecialchars((string) ($payment['provider_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div class="detail-row"><span>Amount</span><strong><?php echo htmlspecialchars((string) ($payment['currency'] ?? 'USD'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars(number_format((float) ($payment['amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
    <?php endif; ?>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
