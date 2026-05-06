<main class="cart-wrap">
    <h1 class="page-title">Shopping Bag</h1>
    <p class="page-subtitle">Review your live floral selections before checkout.</p>

    <?php if (!empty($success)): ?>
        <div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($promoSuccess)): ?>
        <div class="admin-alert success"><?php echo htmlspecialchars((string) $promoSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($promoError)): ?>
        <div class="admin-alert error"><?php echo htmlspecialchars((string) $promoError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="cart-empty-state">
            <span class="cart-empty-state__eyebrow">Shopping Bag</span>
            <h2 class="section-title cart-empty-state__title">Your bag is empty</h2>
            <p class="cart-empty-state__copy">Browse the live catalog and return here when you are ready to place an order.</p>
            <a href="/" class="btn">CONTINUE SHOPPING</a>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-img">
                        <?php if (!empty($item['image_path'])): ?>
                            <div class="cart-item-img__frame">
                                <img class="cart-item-img__media" src="<?php echo htmlspecialchars((string) ($item['image_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        <?php else: ?>
                            <div class="cart-item-img__frame cart-item-img__frame--placeholder">
                                <div class="cart-item-img__placeholder">Floral image</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-head">
                            <div>
                                <h3 class="cart-item-title">
                                    <a href="/product?slug=<?php echo urlencode((string) ($item['product_slug'] ?? '')); ?>" style="text-decoration:none;"><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                                </h3>
                                <p class="cart-item-variant"><?php echo htmlspecialchars((string) ($item['variant_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <strong class="cart-item-price">$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        
                        <p class="cart-item-unit-price">$<?php echo htmlspecialchars(number_format((float) ($item['base_unit_price'] ?? $item['unit_price'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?> each</p>
                        
                        <?php if (!empty($item['addons'])): ?>
                            <div class="cart-item-extras">
                                <strong class="cart-item-extras__title">Extras</strong>
                                <?php foreach (($item['addons'] ?? []) as $addon): ?>
                                    <div class="cart-item-extras__row">
                                        <span>+ <?php echo htmlspecialchars((string) ($addon['addon_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (x<?php echo htmlspecialchars((string) ($addon['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>)</span>
                                        <span>$<?php echo htmlspecialchars(number_format((float) ($addon['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="cart-item-actions">
                            <form method="post" action="/cart/update" class="cart-item-actions__update">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="item_key" value="<?php echo htmlspecialchars((string) ($item['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                    class="cart-item-actions__qty"
                                    id="quantity_<?php echo htmlspecialchars((string) ($item['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    name="quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?php echo htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <button type="submit" class="btn-secondary" style="padding:0.5rem 1rem;font-size:0.75rem;">UPDATE</button>
                            </form>
                            
                            <form method="post" action="/cart/remove" style="margin:0;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="item_key" value="<?php echo htmlspecialchars((string) ($item['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" style="background:none;border:none;color:var(--color-black);font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;text-decoration:underline;cursor:pointer;padding:0;">REMOVE</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary-wrap">
            <div class="cart-summary-panel">
                <div class="cart-summary-row cart-summary-row--subtle">
                    <span>SUBTOTAL (<?php echo htmlspecialchars((string) ($itemCount ?? 0), ENT_QUOTES, 'UTF-8'); ?> ITEMS)</span>
                    <strong>$<?php echo htmlspecialchars(number_format((float) ($subtotal ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <?php if (!empty($appliedPromo) && (float) ($appliedPromo['discount_amount'] ?? 0) > 0): ?>
                    <div class="cart-summary-row cart-summary-row--promo">
                        <span>PROMO (<?php echo htmlspecialchars((string) ($appliedPromo['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</span>
                        <strong>-$<?php echo htmlspecialchars(number_format((float) ($appliedPromo['discount_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="cart-summary-total">
                    <span class="cart-summary-total__label">TOTAL</span>
                    <span>$<?php echo htmlspecialchars(number_format(max(0, (float) ($subtotal ?? 0) - (float) ($appliedPromo['discount_amount'] ?? 0)), 2), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="cart-promo-box">
                    <form method="post" action="/cart/promo/apply" style="display:flex;margin:0;">
                        <?php echo csrf_field(); ?>
                        <input id="cart_promo_code" name="promo_code" type="text" placeholder="GIFT CARD OR DISCOUNT CODE" value="<?php echo htmlspecialchars((string) ($appliedPromo['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="flex:1;border-right:none;">
                        <button type="submit" class="btn" style="padding:0.85rem 1.5rem;">APPLY</button>
                    </form>
                    <?php if (!empty($appliedPromo) && (float) ($appliedPromo['discount_amount'] ?? 0) > 0): ?>
                        <form method="post" action="/cart/promo/remove" style="margin-top:0.5rem;text-align:right;">
                            <?php echo csrf_field(); ?>
                            <button type="submit" style="border:0;background:none;color:var(--color-black);font-size:0.75rem;text-decoration:underline;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;">REMOVE CODE</button>
                        </form>
                    <?php endif; ?>
                </div>

                <a href="/checkout" class="btn btn-block">PROCEED TO CHECKOUT</a>
                <p style="text-align:center;margin-top:1rem;"><a href="/occasions" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--color-gray-dark);text-decoration:underline;">CONTINUE SHOPPING</a></p>
            </div>
        </div>
    <?php endif; ?>
</main>
