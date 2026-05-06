<main class="checkout-wrap">
    <h1 class="page-title">CHECKOUT</h1>
    <p class="page-subtitle">Complete your purchase details below.</p>

    <?php if (!empty($success)): ?>
        <div class="admin-alert success mb-4">
            <?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($orderNumber)): ?> Order reference: <strong><?php echo htmlspecialchars((string) $orderNumber, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="admin-alert error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($promoSuccess)): ?>
        <div class="admin-alert success mb-4"><?php echo htmlspecialchars((string) $promoSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($promoError)): ?>
        <div class="admin-alert error mb-4"><?php echo htmlspecialchars((string) $promoError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="checkout-grid">
        <div class="checkout-panel">
            <?php if (!empty($success) && empty($cartItems)): ?>
                <div style="text-align:center;padding:3rem 0;">
                    <h2 class="section-title">Order Recorded</h2>
                    <p style="color:var(--color-gray-dark);margin-bottom:2rem;">The flow continues into the payment step.</p>
                    <a href="/" class="btn">CONTINUE SHOPPING</a>
                </div>
            <?php else: ?>
                <form method="post" action="/checkout">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="checkout_flow" value="<?php echo !empty($sameDayCheckout) ? 'same-day' : ''; ?>">
                    
                    <h3 class="checkout-panel-title">Customer Details</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                        <div>
                            <label for="customer_name">Customer Name</label>
                            <input id="customer_name" name="customer_name" type="text" required value="<?php echo htmlspecialchars((string) ($formData['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="recipient_name">Recipient Name</label>
                            <input id="recipient_name" name="recipient_name" type="text" value="<?php echo htmlspecialchars((string) ($formData['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2.5rem;">
                        <div>
                            <label for="customer_email">Customer Email</label>
                            <input id="customer_email" name="customer_email" type="email" required value="<?php echo htmlspecialchars((string) ($formData['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="customer_phone">Customer Phone</label>
                            <input id="customer_phone" name="customer_phone" type="text" required value="<?php echo htmlspecialchars((string) ($formData['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <h3 class="checkout-panel-title">Delivery Details</h3>
                    <div style="margin-bottom:1.5rem;">
                        <label for="delivery_address">Delivery Address</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars((string) ($formData['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                        <div>
                            <label for="delivery_zip">Delivery ZIP Code</label>
                            <input id="delivery_zip" name="delivery_zip" type="text" inputmode="numeric" maxlength="5" required value="<?php echo htmlspecialchars((string) ($formData['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="delivery_date">Delivery Date</label>
                            <input id="delivery_date" name="delivery_date" type="date" required min="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) ($formData['delivery_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;margin-bottom:2.5rem;">
                        <div>
                            <label for="delivery_time_slot">Delivery Time Slot</label>
                            <select id="delivery_time_slot" name="delivery_time_slot" required>
                                <option value="">Select a time slot</option>
                                <?php foreach (($deliverySlots ?? []) as $slot): ?>
                                    <option value="<?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($formData['delivery_time_slot'] ?? '') === (string) $slot ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="delivery_instructions">Delivery Instructions</label>
                            <textarea id="delivery_instructions" name="delivery_instructions" rows="2"><?php echo htmlspecialchars((string) ($formData['delivery_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>

                    <h3 class="checkout-panel-title">Gift Message</h3>
                    <div style="margin-bottom:2.5rem;">
                        <textarea id="card_message" name="card_message" rows="3" placeholder="Optional message..."><?php echo htmlspecialchars((string) ($formData['card_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <h3 class="checkout-panel-title">Optional Tip</h3>
                    <div class="radio-pill-list" style="margin-bottom:2.5rem;">
                        <?php foreach (($tipOptions ?? []) as $tipOption): ?>
                            <?php $tipValue = (string) $tipOption; $isSelected = (string) ($formData['tip_amount'] ?? '0.00') === $tipValue; if (($formData['tip_amount'] ?? null) === null && $tipValue === '0.00') { $isSelected = true; } ?>
                            <label class="radio-pill">
                                <input type="radio" name="tip_amount" value="<?php echo htmlspecialchars($tipValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                                <div class="radio-pill-content">
                                    <span><?php echo $tipValue === '0.00' ? 'No tip' : '$' . htmlspecialchars(number_format((float) $tipValue, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-bottom:2rem;padding:1.5rem;border:1px solid var(--color-gray-light);background:var(--color-off-white);">
                        <label for="policy_accepted" style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;margin:0;">
                            <input id="policy_accepted" name="policy_accepted" type="checkbox" value="1" required <?php echo (string) ($formData['policy_accepted'] ?? '') === '1' ? 'checked' : ''; ?> style="width:auto;margin-top:0.15rem;">
                            <span style="font-size:0.8rem;color:var(--color-gray-dark);text-transform:none;letter-spacing:normal;font-weight:400;">I accept that all sales are final, online cancellation is unavailable, refunds are not guaranteed online, and I must contact the store directly for any issue.</span>
                        </label>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <a href="/cart" class="btn-secondary" style="border:none;">← BACK TO BAG</a>
                        <button type="submit" class="btn">CONTINUE TO PAYMENT</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div style="display:flex;flex-direction:column;gap:1.5rem;position:sticky;top:120px;">
            <!-- PROMO -->
            <div class="checkout-panel">
                <p class="eyebrow" style="margin-bottom:1rem;font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.85rem;font-weight:600;">Promo Code</p>
                <form method="post" action="/checkout/promo/apply" style="display:flex;gap:0.5rem;margin:0;">
                    <?php echo csrf_field(); ?>
                    <input id="checkout_promo_code" name="promo_code" type="text" placeholder="Promo code" value="<?php echo htmlspecialchars((string) ($appliedPromo['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="flex:1;">
                    <button type="submit" class="btn-secondary">APPLY</button>
                </form>
                <?php if (!empty($appliedPromo) && (float) ($appliedPromo['discount_amount'] ?? 0) > 0): ?>
                    <form method="post" action="/checkout/promo/remove" style="margin-top:0.5rem;">
                        <?php echo csrf_field(); ?>
                        <span style="font-size:0.85rem;">Code <strong><?php echo htmlspecialchars((string) ($appliedPromo['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong> applied</span>
                        <button type="submit" style="border:none;background:none;text-decoration:underline;cursor:pointer;font-size:0.8rem;padding:0;margin-left:0.5rem;">(Remove)</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- TOTALS -->
            <div class="checkout-panel">
                <h2 class="checkout-panel-title">Order Summary</h2>
                
                <?php if (!empty($cartItems)): ?>
                    <div style="border-bottom:1px solid var(--color-gray-light);padding-bottom:1.5rem;margin-bottom:1.5rem;">
                        <?php foreach (($cartItems ?? []) as $item): ?>
                            <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:0.75rem;">
                                <div>
                                    <strong style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.05em;"><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (x<?php echo htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?>)</strong>
                                </div>
                                <strong style="font-family:var(--font-body);">$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--color-gray-light);font-size:0.85rem;color:var(--color-gray-dark);">
                    <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong style="color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($subtotal ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <?php if (!empty($appliedPromo) && (float) ($appliedPromo['discount_amount'] ?? 0) > 0): ?><div style="display:flex;justify-content:space-between;color:var(--color-black);"><span>Promo</span><strong>-$<?php echo htmlspecialchars(number_format((float) ($appliedPromo['discount_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div><?php endif; ?>
                    <div style="display:flex;justify-content:space-between;"><span>Delivery</span><strong style="color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($deliveryFee ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div style="display:flex;justify-content:space-between;"><span>Tax</span><strong style="color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($taxAmount ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <?php if ((float) ($tipAmount ?? 0) > 0): ?><div style="display:flex;justify-content:space-between;color:var(--color-black);"><span>Tip</span><strong>$<?php echo htmlspecialchars(number_format((float) ($tipAmount ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div><?php endif; ?>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:flex-end;">
                    <span style="font-family:var(--font-heading);font-weight:600;color:var(--color-black);text-transform:uppercase;letter-spacing:0.1em;">Total</span>
                    <strong style="font-size:1.5rem;color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($totalAmount ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>
        </div>
    </div>
</main>
