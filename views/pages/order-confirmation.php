<main class="page-order-confirmation" style="margin-top:0;background:var(--color-white);min-height:80vh;">
    <?php if (empty($order)): ?>
        <!-- HEADER -->
        <div style="padding:4rem 1rem;text-align:center;border-bottom:1px solid var(--color-gray-light);background:var(--color-off-white);margin-bottom:3rem;">
            <div class="container">
                <h1 style="font-family:var(--font-heading);color:var(--color-black);font-size:3rem;font-weight:500;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.15em;">
                    ORDER NOT FOUND
                </h1>
                <p style="color:var(--color-gray-dark);font-size:0.9rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 auto;">
                    The requested order confirmation could not be found.
                </p>
            </div>
        </div>
        <div class="container mb-5" style="text-align:center;max-width:600px;margin:0 auto;">
            <?php if (!empty($info)): ?>
                <div class="admin-alert info mb-4"><?php echo htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div style="display:flex;gap:1rem;justify-content:center;">
                <a href="/" class="btn">RETURN HOME</a>
            </div>
        </div>
    <?php else: ?>
        <!-- HEADER -->
        <div style="padding:4rem 1rem;text-align:center;border-bottom:1px solid var(--color-gray-light);background:var(--color-off-white);margin-bottom:3rem;">
            <div class="container">
                <h1 style="font-family:var(--font-heading);color:var(--color-black);font-size:3rem;font-weight:500;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.15em;">
                    ORDER CONFIRMED
                </h1>
                <p style="color:var(--color-gray-dark);font-size:0.9rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 auto;">
                    Thank you for your purchase.
                </p>
            </div>
        </div>

        <div class="container mb-5">
            <?php if (!empty($success)): ?><div class="admin-alert success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if (!empty($info)): ?><div class="admin-alert info mb-4"><?php echo htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 400px;gap:3rem;align-items:start;">
                <!-- MAIN CONTENT -->
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                    
                    <div class="checkout-panel">
                        <h3 class="checkout-panel-title">Order Details</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;font-size:0.85rem;">
                            <div>
                                <span style="display:block;text-transform:uppercase;color:var(--color-gray-dark);margin-bottom:0.25rem;letter-spacing:0.05em;">Customer</span>
                                <span style="color:var(--color-black);font-weight:500;"><?php echo htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div>
                                <span style="display:block;text-transform:uppercase;color:var(--color-gray-dark);margin-bottom:0.25rem;letter-spacing:0.05em;">Recipient</span>
                                <span style="color:var(--color-black);font-weight:500;"><?php echo htmlspecialchars((string) ($order['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div>
                                <span style="display:block;text-transform:uppercase;color:var(--color-gray-dark);margin-bottom:0.25rem;letter-spacing:0.05em;">Delivery Date</span>
                                <span style="color:var(--color-black);font-weight:500;"><?php echo htmlspecialchars((string) ($order['delivery_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div>
                                <span style="display:block;text-transform:uppercase;color:var(--color-gray-dark);margin-bottom:0.25rem;letter-spacing:0.05em;">Delivery Slot</span>
                                <span style="color:var(--color-black);font-weight:500;"><?php echo htmlspecialchars((string) ($order['delivery_time_slot'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div style="grid-column:1/-1;">
                                <span style="display:block;text-transform:uppercase;color:var(--color-gray-dark);margin-bottom:0.25rem;letter-spacing:0.05em;">Delivery Address</span>
                                <span style="display:block;color:var(--color-black);font-weight:500;white-space:pre-line;line-height:1.5;background:var(--color-off-white);padding:1rem;border:1px solid var(--color-gray-light);"><?php echo htmlspecialchars((string) ($order['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-panel">
                        <h3 class="checkout-panel-title">Items</h3>
                        <?php if (($items ?? []) === []): ?>
                            <p style="color:var(--color-gray-dark);font-style:italic;">No order items were found for this order.</p>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                                <?php foreach (($items ?? []) as $item): ?>
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:1.5rem;border-bottom:1px solid var(--color-gray-light);last-child:border:none;last-child:padding-bottom:0;">
                                        <div>
                                            <strong style="display:block;color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;"><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if (!empty($item['variant_name'])): ?>
                                                <div style="font-size:0.75rem;color:var(--color-gray-dark);margin-bottom:0.25rem;text-transform:uppercase;"><?php echo htmlspecialchars((string) ($item['variant_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endif; ?>
                                            <div style="font-size:0.75rem;color:var(--color-gray-dark);margin-bottom:0.5rem;text-transform:uppercase;">QTY <?php echo htmlspecialchars((string) ($item['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php foreach (($item['addons'] ?? []) as $addon): ?>
                                                <div style="font-size:0.75rem;color:var(--color-gray-dark);text-transform:uppercase;">+ <?php echo htmlspecialchars((string) ($addon['addon_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (x<?php echo htmlspecialchars((string) ($addon['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>) - $<?php echo htmlspecialchars(number_format((float) ($addon['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <strong style="color:var(--color-black);font-family:var(--font-body);">$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- SIDEBAR -->
                <aside style="display:flex;flex-direction:column;gap:1.5rem;position:sticky;top:1rem;">
                    <!-- Order Snapshot -->
                    <div class="checkout-panel">
                        <h3 class="checkout-panel-title">Order Info</h3>
                        <div style="display:flex;flex-direction:column;gap:1.25rem;font-size:0.85rem;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:var(--color-gray-dark);text-transform:uppercase;">Order Number</span>
                                <strong style="color:var(--color-black);"><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:var(--color-gray-dark);text-transform:uppercase;">Status</span>
                                <span style="display:inline-block;padding:0.25rem 0.75rem;font-size:0.7rem;font-weight:600;background:var(--color-black);color:var(--color-white);text-transform:uppercase;letter-spacing:0.1em;"><?php echo htmlspecialchars((string) (($publicTracking['label'] ?? '') !== '' ? $publicTracking['label'] : ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="checkout-panel">
                        <h3 class="checkout-panel-title">Totals</h3>
                        <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--color-gray-light);font-size:0.85rem;color:var(--color-gray-dark);">
                            <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong style="color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($order['subtotal'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php if (!empty($order['promo_code']) && (float) ($order['promo_discount_amount'] ?? 0) > 0): ?>
                                <div style="display:flex;justify-content:space-between;color:var(--color-black);"><span>Promo (<?php echo htmlspecialchars((string) ($order['promo_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</span><strong>-$<?php echo htmlspecialchars(number_format((float) ($order['promo_discount_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php endif; ?>
                            <div style="display:flex;justify-content:space-between;"><span>Delivery</span><strong style="color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($order['delivery_fee'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div style="display:flex;justify-content:space-between;"><span>Tax</span><strong style="color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($order['tax_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php if ((float) ($order['tip_amount'] ?? 0) > 0): ?>
                                <div style="display:flex;justify-content:space-between;color:var(--color-black);"><span>Tip</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['tip_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:flex-end;">
                            <span style="font-family:var(--font-heading);font-weight:600;color:var(--color-black);text-transform:uppercase;letter-spacing:0.1em;">Total</span>
                            <strong style="font-size:1.5rem;color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($payment['payment_reference'])): ?>
                        <a href="/payment?<?php echo htmlspecialchars((string) http_build_query(['reference' => (string) $payment['payment_reference'], 'token' => (string) ($accessToken ?? '')]), ENT_QUOTES, 'UTF-8'); ?>" class="btn">VIEW PAYMENT</a>
                    <?php endif; ?>
                    <a href="/" class="btn-secondary" style="border:none;">CONTINUE SHOPPING</a>
                </aside>
            </div>
        </div>
    <?php endif; ?>
</main>
