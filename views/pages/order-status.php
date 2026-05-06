<?php $lookup = is_array($lookup ?? null) ? $lookup : []; ?>
<main class="page-order-status" style="margin-top:0;background:var(--color-white);min-height:80vh;">
    <!-- HEADER -->
    <div style="padding:4rem 1rem;text-align:center;border-bottom:1px solid var(--color-gray-light);background:var(--color-off-white);margin-bottom:3rem;">
        <div class="container">
            <h1 style="font-family:var(--font-heading);color:var(--color-black);font-size:3rem;font-weight:500;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.15em;">
                ORDER TRACKING
            </h1>
            <p style="color:var(--color-gray-dark);font-size:0.9rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 auto;">
                Look up your order details.
            </p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (!empty($success)): ?><div class="admin-alert success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="admin-alert error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 400px;gap:3rem;align-items:start;margin-bottom:4rem;">
            <!-- FORM MAIN CONTENT -->
            <div style="background:var(--color-white);padding:2rem;border:1px solid var(--color-gray-light);">
                <form method="post" action="/order-status" style="display:flex;flex-direction:column;gap:1.5rem;">
                    <?php echo csrf_field(); ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                        <div>
                            <label for="order_number" style="display:block;font-size:0.75rem;font-weight:600;text-transform:uppercase;color:var(--color-black);margin-bottom:0.5rem;letter-spacing:0.1em;">Order Number</label>
                            <input id="order_number" name="order_number" type="text" required value="<?php echo htmlspecialchars((string) ($lookup['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="SF-1234..." style="width:100%;padding:0.75rem;border:1px solid var(--color-gray-light);">
                        </div>
                        <div>
                            <label for="customer_email" style="display:block;font-size:0.75rem;font-weight:600;text-transform:uppercase;color:var(--color-black);margin-bottom:0.5rem;letter-spacing:0.1em;">Customer Email</label>
                            <input id="customer_email" name="customer_email" type="email" required value="<?php echo htmlspecialchars((string) ($lookup['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="you@example.com" style="width:100%;padding:0.75rem;border:1px solid var(--color-gray-light);">
                        </div>
                    </div>
                    <div style="display:flex;gap:1rem;margin-top:1rem;">
                        <button type="submit" class="btn">LOOK UP ORDER</button>
                    </div>
                </form>
            </div>

            <!-- LOOKUP SIDEBAR -->
            <aside style="background:var(--color-off-white);padding:2rem;border:1px solid var(--color-gray-light);">
                <p class="eyebrow" style="color:var(--color-black);margin-bottom:1rem;font-weight:600;">NEED HELP?</p>
                <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:2rem;font-size:0.85rem;color:var(--color-gray-dark);">
                    <p>Use the exact order number from your confirmation or notification.</p>
                    <p>Use the same email entered at checkout.</p>
                </div>
            </aside>
        </div>

        <?php if (!empty($order)): ?>
            <div style="display:grid;grid-template-columns:1fr 400px;gap:3rem;align-items:start;">
                <!-- DETAILS MAIN CONTENT -->
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                    <div class="checkout-panel">
                        <h3 class="checkout-panel-title">Tracking Summary</h3>
                        <div style="display:flex;flex-direction:column;gap:1rem;font-size:0.85rem;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:var(--color-gray-dark);text-transform:uppercase;">Order Number</span>
                                <strong style="color:var(--color-black);"><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:var(--color-gray-dark);text-transform:uppercase;">Status</span>
                                <span style="display:inline-block;padding:0.25rem 0.75rem;font-size:0.7rem;font-weight:600;background:var(--color-black);color:var(--color-white);text-transform:uppercase;letter-spacing:0.1em;"><?php echo htmlspecialchars((string) ($publicTracking['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <?php if (!empty($publicTracking['updated_at'])): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <span style="color:var(--color-gray-dark);text-transform:uppercase;">Status Updated</span>
                                    <strong style="color:var(--color-black);"><?php echo htmlspecialchars((string) ($publicTracking['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($publicTracking['note'])): ?>
                            <p style="margin-top:1.5rem;font-size:0.85rem;color:var(--color-gray-dark);background:var(--color-off-white);padding:1rem;border:1px solid var(--color-gray-light);white-space:pre-line;"><?php echo htmlspecialchars((string) ($publicTracking['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="checkout-panel">
                        <h3 class="checkout-panel-title">Order Details</h3>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;font-size:0.85rem;">
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
                            <div>
                                <span style="display:block;text-transform:uppercase;color:var(--color-gray-dark);margin-bottom:0.25rem;letter-spacing:0.05em;">Payment</span>
                                <span style="color:var(--color-black);font-weight:500;"><?php echo htmlspecialchars((string) ($paymentStatusLabel ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TOTALS SIDEBAR -->
                <aside style="display:flex;flex-direction:column;gap:1.5rem;position:sticky;top:1rem;">
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
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>
