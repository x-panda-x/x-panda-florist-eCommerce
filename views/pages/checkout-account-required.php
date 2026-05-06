<main class="checkout-wrap">
    <h1 class="page-title"><?php echo !empty($sameDayCheckout) ? 'ACCOUNT REQUIRED FOR SAME-DAY CHECKOUT' : 'ACCOUNT REQUIRED FOR CHECKOUT'; ?></h1>
    <p class="page-subtitle">
        <?php echo !empty($sameDayCheckout)
            ? 'Sign in or create an account before we can finalize an urgent same-day order.'
            : 'Sign in or create an account before we can finalize the order.'; ?>
    </p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="checkout-grid">
        <div class="checkout-panel">
            <h2 class="checkout-panel-title">Why We Require An Account</h2>
            <div class="stack-md">
                <p class="site-note" style="font-size:1rem;color:var(--color-gray-dark);">
                    An account is required to place an order so you can track delivery, save addresses, manage reminders, and keep delivery details accurate.
                </p>
                <?php if (!empty($sameDayCheckout)): ?>
                    <div class="cart-promo-box" style="margin:0;">
                        <p class="eyebrow" style="margin:0 0 0.6rem;">Same-Day Orders</p>
                        <p class="site-note" style="margin:0;">For urgent same-day orders, we use your account to confirm delivery information quickly and reduce missed-delivery risk.</p>
                    </div>
                <?php endif; ?>
                <div class="stack-sm">
                    <a href="/account/login?return_to=<?php echo urlencode((string) ($returnTo ?? '/checkout')); ?>" class="btn btn-block">Sign In To Continue</a>
                    <a href="/account/register?return_to=<?php echo urlencode((string) ($returnTo ?? '/checkout')); ?>" class="btn btn-block">Create Account</a>
                    <a href="/cart" class="account-required-link">Back To Bag</a>
                </div>
            </div>
        </div>

        <aside class="checkout-panel">
            <h2 class="checkout-panel-title">Order Preview</h2>
            <div style="display:flex;flex-direction:column;gap:0.85rem;">
                <?php foreach (($cartItems ?? []) as $item): ?>
                    <div class="checkout-gate-item">
                        <div>
                            <strong><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="admin-note"><?php echo htmlspecialchars((string) ($item['variant_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> x<?php echo htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <strong>$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary-total" style="margin-top:1.5rem;margin-bottom:0;">
                <span class="cart-summary-total__label">Subtotal</span>
                <span>$<?php echo htmlspecialchars(number_format((float) ($subtotal ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <p class="site-note" style="margin:0.85rem 0 0;">Your cart is preserved. After you sign in, you will return directly to checkout.</p>
        </aside>
    </div>
</main>
