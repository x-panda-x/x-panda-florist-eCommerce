<?php
    $items = is_array($items ?? null) ? $items : [];
    $primaryItem = $items[0] ?? null;
    $itemCount = count($items);
    $status = (string) ($order['status'] ?? 'pending');
    $statusLabel = ucwords(str_replace('_', ' ', $status));
    $cardMessage = trim((string) ($order['card_message'] ?? ''));

    $money = static fn (mixed $value): string => '$' . number_format((float) ($value ?? 0), 2);
    $text = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $imageFor = static function (array $item): string {
        $path = trim((string) ($item['image_path'] ?? ''));

        return $path !== '' ? $path : '/assets/images/placeholder-bouquet.jpg';
    };
?>

<div class="order-detail-page">
    <section class="order-detail-header admin-card">
        <div class="order-detail-header__main">
            <p class="admin-kicker">Order Detail</p>
            <div class="order-detail-header__title-row">
                <h2 class="admin-title"><?php echo $text($order['order_number'] ?? ''); ?></h2>
                <span class="admin-status-pill is-<?php echo $text(str_replace('_', '-', $status)); ?>"><?php echo $text($statusLabel); ?></span>
            </div>
            <p class="admin-subtitle">Created <?php echo $text($order['created_at'] ?? ''); ?></p>
        </div>

        <div class="order-detail-header__side">
            <a href="/admin/orders" class="admin-button-secondary">Back to Orders</a>
            <div class="order-detail-quick-meta">
                <div>
                    <span>Total</span>
                    <strong><?php echo $text($money($order['total_amount'] ?? 0)); ?></strong>
                </div>
                <div>
                    <span>Delivery</span>
                    <strong><?php echo $text($order['delivery_date'] ?? ''); ?></strong>
                </div>
                <div>
                    <span>Items</span>
                    <strong><?php echo $text((string) $itemCount); ?></strong>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error"><?php echo $text($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success"><?php echo $text($success); ?></div>
    <?php endif; ?>

    <section class="order-product-spotlight admin-card">
        <div class="order-card-heading">
            <div>
                <p class="admin-kicker"><?php echo $itemCount === 1 ? 'Ordered Product' : 'Ordered Products'; ?></p>
                <h3 class="order-card-title">What the customer ordered</h3>
            </div>
            <span class="order-card-count"><?php echo $text((string) $itemCount); ?> item<?php echo $itemCount === 1 ? '' : 's'; ?></span>
        </div>

        <?php if ($primaryItem === null): ?>
            <p class="admin-empty-state">No ordered items are linked to this order.</p>
        <?php else: ?>
            <div class="order-product-feature">
                <div class="order-product-feature__media">
                    <img src="<?php echo $text($imageFor($primaryItem)); ?>" alt="<?php echo $text($primaryItem['product_name'] ?? 'Ordered product'); ?>">
                </div>
                <div class="order-product-feature__body">
                    <div class="order-product-feature__top">
                        <div>
                            <h3><?php echo $text($primaryItem['product_name'] ?? ''); ?></h3>
                            <?php if (!empty($primaryItem['product_slug'])): ?>
                                <p><?php echo $text($primaryItem['product_slug']); ?></p>
                            <?php endif; ?>
                        </div>
                        <strong><?php echo $text($money($primaryItem['line_total'] ?? 0)); ?></strong>
                    </div>

                    <div class="order-product-metrics">
                        <div><span>Variant / Size</span><strong><?php echo $text(($primaryItem['variant_name'] ?? '') !== '' ? $primaryItem['variant_name'] : 'Standard'); ?></strong></div>
                        <div><span>Quantity</span><strong><?php echo $text($primaryItem['quantity'] ?? ''); ?></strong></div>
                        <div><span>Unit Price</span><strong><?php echo $text($money($primaryItem['unit_price'] ?? 0)); ?></strong></div>
                    </div>

                    <?php if (!empty($primaryItem['addons'])): ?>
                        <div class="order-product-addons">
                            <span>Add-ons</span>
                            <?php foreach (($primaryItem['addons'] ?? []) as $addon): ?>
                                <div>
                                    <?php echo $text($addon['addon_name'] ?? ''); ?>
                                    x<?php echo $text($addon['quantity'] ?? 0); ?>
                                    <strong><?php echo $text($money($addon['line_total'] ?? 0)); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($itemCount > 1): ?>
                <div class="order-items-compact-grid">
                    <?php foreach (array_slice($items, 1) as $item): ?>
                        <article class="order-item-compact-card">
                            <img src="<?php echo $text($imageFor($item)); ?>" alt="<?php echo $text($item['product_name'] ?? 'Ordered product'); ?>">
                            <div>
                                <strong><?php echo $text($item['product_name'] ?? ''); ?></strong>
                                <span><?php echo $text(($item['variant_name'] ?? '') !== '' ? $item['variant_name'] : 'Standard'); ?> · Qty <?php echo $text($item['quantity'] ?? ''); ?></span>
                                <em><?php echo $text($money($item['line_total'] ?? 0)); ?></em>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <div class="order-detail-layout">
        <div class="order-detail-column">
            <section class="admin-card order-detail-section">
                <div class="order-card-heading">
                    <div>
                        <p class="admin-kicker">Order Summary</p>
                        <h3 class="order-card-title">Totals and status</h3>
                    </div>
                </div>
                <div class="order-detail-list">
                    <div><span>Status</span><strong><span class="admin-status-pill is-<?php echo $text(str_replace('_', '-', $status)); ?>"><?php echo $text($statusLabel); ?></span></strong></div>
                    <div><span>Subtotal</span><strong><?php echo $text($money($order['subtotal'] ?? 0)); ?></strong></div>
                    <?php if (!empty($order['promo_code']) && (float) ($order['promo_discount_amount'] ?? 0) > 0): ?>
                        <div><span>Promo</span><strong><?php echo $text($order['promo_code'] ?? ''); ?> (-<?php echo $text($money($order['promo_discount_amount'] ?? 0)); ?>)</strong></div>
                    <?php endif; ?>
                    <div><span>Delivery Fee</span><strong><?php echo $text($money($order['delivery_fee'] ?? 0)); ?></strong></div>
                    <div><span>Tax</span><strong><?php echo $text($money($order['tax_amount'] ?? 0)); ?></strong></div>
                    <div><span>Tip</span><strong><?php echo $text($money($order['tip_amount'] ?? 0)); ?></strong></div>
                    <div class="is-total"><span>Total</span><strong><?php echo $text($money($order['total_amount'] ?? 0)); ?></strong></div>
                    <div><span>Updated</span><strong><?php echo $text($order['updated_at'] ?? ''); ?></strong></div>
                </div>
            </section>

            <section class="admin-card order-detail-section">
                <div class="order-card-heading">
                    <div>
                        <p class="admin-kicker">Customer Details</p>
                        <h3 class="order-card-title"><?php echo $text($order['customer_name'] ?? 'Customer'); ?></h3>
                    </div>
                </div>
                <div class="order-detail-list">
                    <div><span>Email</span><strong><?php echo $text($order['customer_email'] ?? ''); ?></strong></div>
                    <div><span>Phone</span><strong><?php echo $text($order['customer_phone'] ?? ''); ?></strong></div>
                    <div><span>Recipient</span><strong><?php echo $text($order['recipient_name'] ?? ''); ?></strong></div>
                </div>
            </section>

            <section class="admin-card order-detail-section">
                <div class="order-card-heading">
                    <div>
                        <p class="admin-kicker">Delivery Details</p>
                        <h3 class="order-card-title"><?php echo $text($order['delivery_date'] ?? ''); ?></h3>
                    </div>
                </div>
                <div class="order-detail-list">
                    <div><span>Time Slot</span><strong><?php echo $text($order['delivery_time_slot'] ?? ''); ?></strong></div>
                    <div><span>ZIP</span><strong><?php echo $text($order['delivery_zip'] ?? ''); ?></strong></div>
                </div>
                <div class="order-note-panel">
                    <span>Address</span>
                    <p><?php echo nl2br($text($order['delivery_address'] ?? '')); ?></p>
                </div>
            </section>
        </div>

        <div class="order-detail-column">
            <section class="admin-card order-detail-section">
                <div class="order-card-heading">
                    <div>
                        <p class="admin-kicker">Payment Details</p>
                        <h3 class="order-card-title"><?php echo empty($payment) ? 'No payment linked' : $text($payment['payment_reference'] ?? 'Payment'); ?></h3>
                    </div>
                </div>
                <?php if (empty($payment)): ?>
                    <p class="admin-note">No payment record is linked to this order yet.</p>
                <?php else: ?>
                    <div class="order-detail-list">
                        <div><span>Provider</span><strong><?php echo $text($payment['provider_name'] ?? ''); ?></strong></div>
                        <div><span>Status</span><strong><span class="admin-status-pill"><?php echo $text($payment['status'] ?? 'pending'); ?></span></strong></div>
                        <div><span>Amount</span><strong><?php echo $text($payment['currency'] ?? 'USD'); ?> <?php echo $text(number_format((float) ($payment['amount'] ?? 0), 2)); ?></strong></div>
                        <?php if (!empty($payment['provider_reference'])): ?>
                            <div><span>Provider Ref</span><strong><?php echo $text($payment['provider_reference'] ?? ''); ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($payment['failure_message'])): ?>
                        <div class="order-note-panel">
                            <span>Failure Message</span>
                            <p><?php echo $text($payment['failure_message'] ?? ''); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="admin-card order-detail-section order-card-note-section">
                <div class="order-card-heading order-card-note-heading">
                    <div>
                        <p class="admin-kicker">Customer Note / Card Message</p>
                        <h3 class="order-card-title">Printable gift note</h3>
                    </div>
                    <a class="admin-button-secondary order-print-note-button" href="/admin/orders/card-note?id=<?php echo urlencode((string) ($order['id'] ?? '')); ?>" target="_blank" rel="noopener">Print Card Note</a>
                </div>
                <div class="order-card-message-preview">
                    <span>Card Message</span>
                    <?php if ($cardMessage !== ''): ?>
                        <p><?php echo nl2br($text($cardMessage)); ?></p>
                    <?php else: ?>
                        <p class="is-empty">No card message was provided for this order.</p>
                    <?php endif; ?>
                </div>
                <div class="order-note-panel">
                    <span>Delivery Instructions</span>
                    <p><?php echo nl2br($text($order['delivery_instructions'] ?? '')); ?></p>
                </div>
                <div class="order-print-note-help">
                    <div>
                        <strong>A4 landscape tri-fold</strong>
                        <span>Opens a clean folded insert with branding, the message, and order reference details.</span>
                    </div>
                </div>
            </section>

            <section class="admin-card order-detail-section">
                <div class="order-card-heading">
                    <div>
                        <p class="admin-kicker">Status Update</p>
                        <h3 class="order-card-title">Manage order</h3>
                    </div>
                </div>
                <form class="order-admin-form" method="post" action="/admin/orders/update-status">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $text($order['id'] ?? ''); ?>">
                    <div class="admin-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (($statusOptions ?? []) as $statusOption): ?>
                                <option value="<?php echo $text($statusOption); ?>" <?php echo $status === (string) $statusOption ? 'selected' : ''; ?>>
                                    <?php echo $text(ucwords(str_replace('_', ' ', (string) $statusOption))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="admin-button">Save Status</button>
                </form>
            </section>

            <section class="admin-card order-detail-section">
                <div class="order-card-heading">
                    <div>
                        <p class="admin-kicker">Public Tracking</p>
                        <h3 class="order-card-title">Customer-facing status</h3>
                    </div>
                </div>
                <form class="order-admin-form" method="post" action="/admin/orders/update-public-tracking">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $text($order['id'] ?? ''); ?>">
                    <div class="admin-field">
                        <label for="tracking_status_label">Customer-Facing Status Label</label>
                        <input id="tracking_status_label" name="tracking_status_label" type="text" maxlength="190" value="<?php echo $text($order['tracking_status_label'] ?? ''); ?>" placeholder="Leave blank to use the default label from order status">
                    </div>
                    <div class="admin-field">
                        <label for="tracking_public_note">Public Tracking Note</label>
                        <textarea id="tracking_public_note" name="tracking_public_note" rows="4" placeholder="Optional public note visible on the order tracking page"><?php echo $text($order['tracking_public_note'] ?? ''); ?></textarea>
                    </div>
                    <div class="order-detail-list order-detail-list--compact">
                        <div><span>Status Updated At</span><strong><?php echo $text($order['status_updated_at'] ?? ''); ?></strong></div>
                    </div>
                    <button type="submit" class="admin-button">Save Public Tracking</button>
                </form>
            </section>
        </div>
    </div>
</div>
