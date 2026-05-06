<?php
$publicBlocks = public_page_blocks(true);
$paymentHelp = $publicBlocks['page.payment.help'] ?? [
    'subheading' => 'Payment Placeholder',
    'heading' => 'A clearer status screen for the existing placeholder payment flow.',
    'body_text' => 'No real card gateway is connected here yet. The live flow still creates and updates placeholder payment records exactly as before, including saved add-on selections.',
    'items' => [
        ['title' => 'This screen is redesigned only. The allowed simulation behavior and token-based access remain unchanged.'],
        ['title' => 'You can still simulate placeholder payment outcomes here. This is existing behavior with cleaner presentation only.'],
    ],
];
$paymentHelpItems = is_array($paymentHelp['items'] ?? null) ? $paymentHelp['items'] : [];
?>
<main class="page-payment" style="margin-top:0;background:#fcfcfc;min-height:80vh;">
    <!-- HEADER -->
    <div style="padding:4rem 1rem;text-align:center;border-bottom:1px solid #eeeee4;background:#fff;margin-bottom:3rem;">
        <div class="container">
            <p class="eyebrow" style="color:var(--brand-pink);"><?php echo htmlspecialchars((string) ($paymentHelp['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <h1 style="color:var(--brand-teal);font-size:3.5rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;font-style:italic;">
                <?php echo htmlspecialchars((string) ($paymentHelp['heading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p style="color:var(--brand-muted);font-size:1.1rem;max-width:600px;margin:0 auto;line-height:1.6;">
                <?php echo htmlspecialchars((string) ($paymentHelp['body_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (empty($payment) || empty($order)): ?>
            <div style="text-align:center;padding:5rem;background:#fff;border-radius:8px;border:1px solid #eeeee4;max-width:600px;margin:0 auto;">
                <p class="eyebrow" style="color:var(--brand-pink);">Payment</p>
                <h2 style="color:var(--brand-teal);margin-bottom:1rem;text-transform:uppercase;">Payment Not Found</h2>
                <?php if (!empty($error)): ?><div class="flash flash-error" style="margin-bottom:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <?php if (!empty($info)): ?><div class="flash flash-info" style="margin-bottom:1rem;"><?php echo htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <p style="color:var(--brand-muted);margin-bottom:2rem;">The requested payment record could not be found.</p>
                <div style="display:flex;gap:1rem;justify-content:center;">
                    <a href="/" class="btn" style="padding:1rem 2rem;">Return Home</a>
                    <a href="/cart" class="btn-secondary" style="padding:1rem 2rem;">View Cart</a>
                </div>
            </div>
        <?php else: ?>
            <?php $items = is_array($items ?? null) ? $items : []; ?>
            
            <?php if (!empty($success)): ?>
                <div class="flash flash-success mb-4" style="background:#eaf4f4;color:var(--brand-teal);border-color:var(--brand-teal);">
                    <?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (!empty($orderNumber)): ?> Order reference: <strong><?php echo htmlspecialchars((string) $orderNumber, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?><div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if (!empty($info)): ?><div class="flash flash-info mb-4" style="background:#f0f8ff;color:#005b9f;border-color:#b3d4fc;"><?php echo htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 400px;gap:3rem;align-items:start;">
                <!-- MAIN CONTENT -->
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                    
                    <div style="background:#fff;padding:2rem;border-radius:8px;border:1px solid #eeeee4;">
                        <p class="eyebrow" style="color:var(--brand-pink);margin-bottom:1rem;">Payment Snapshot</p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                            <div><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Order Number</span><strong style="color:var(--brand-ink);"><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Payment Reference</span><strong style="color:var(--brand-ink);"><?php echo htmlspecialchars((string) ($payment['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Provider</span><strong style="color:var(--brand-ink);"><?php echo htmlspecialchars((string) ($payment['provider_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Status</span><span style="display:inline-block;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;font-weight:600;background:#eee;color:#333;text-transform:uppercase;"><?php echo htmlspecialchars((string) ($payment['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                            <div style="grid-column:1/-1;"><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Amount</span><strong style="color:var(--brand-teal);font-size:1.5rem;"><?php echo htmlspecialchars((string) ($payment['currency'] ?? 'USD'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars(number_format((float) ($payment['amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        </div>
                    </div>

                    <?php if ((string) ($payment['status'] ?? '') === 'paid'): ?>
                        <div style="padding:1.5rem;background:#eaf4f4;border:1px solid var(--brand-teal);border-radius:8px;color:var(--brand-teal);">Local QA has marked this placeholder payment as paid. No real charge, authorization, or provider request has occurred.</div>
                    <?php elseif (in_array((string) ($payment['status'] ?? ''), ['failed', 'cancelled'], true)): ?>
                        <div style="padding:1.5rem;background:#fff3f3;border:1px solid #ffcdd2;border-radius:8px;color:#d32f2f;">This placeholder payment is in a terminal QA state. No real charge occurred.</div>
                    <?php endif; ?>

                    <div style="background:#fcfcfc;padding:2rem;border-radius:8px;border:1px dashed #ccc;">
                        <p class="eyebrow" style="color:var(--brand-pink);margin-bottom:1rem;">Local QA Simulation</p>
                        <p style="color:var(--brand-muted);margin-bottom:1.5rem;"><?php echo htmlspecialchars((string) (($paymentHelpItems[1]['title'] ?? 'You can still simulate placeholder payment outcomes here. This is existing behavior with cleaner presentation only.')), ENT_QUOTES, 'UTF-8'); ?></p>

                        <?php if (($simulationOptions ?? []) === []): ?>
                            <p style="color:var(--brand-muted);font-style:italic;">This payment is already in a terminal QA state and can no longer be simulated from this page.</p>
                        <?php else: ?>
                            <form method="post" action="/payment/simulate" style="display:flex;gap:1rem;flex-wrap:wrap;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="reference" value="<?php echo htmlspecialchars((string) ($payment['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars((string) ($accessToken ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach (($simulationOptions ?? []) as $option): ?>
                                    <button type="submit" name="status" value="<?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?>" class="btn-secondary" style="padding:0.75rem 1.5rem;">
                                        Simulate <?php echo htmlspecialchars(ucfirst((string) $option), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                <?php endforeach; ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($payment['provider_reference']) || !empty($payment['failure_message'])): ?>
                        <div style="background:#fff;padding:2rem;border-radius:8px;border:1px solid #eeeee4;">
                            <p class="eyebrow" style="color:var(--brand-pink);margin-bottom:1rem;">Simulation Details</p>
                            <div style="display:flex;flex-direction:column;gap:1rem;">
                                <?php if (!empty($payment['provider_reference'])): ?>
                                    <div><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Simulation Ref</span><strong style="color:var(--brand-ink);"><?php echo htmlspecialchars((string) ($payment['provider_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <?php endif; ?>
                                <?php if (!empty($payment['failure_message'])): ?>
                                    <div><span style="display:block;font-size:0.8rem;text-transform:uppercase;color:var(--brand-muted);margin-bottom:0.25rem;">Simulation Note</span><p style="margin:0;color:#d32f2f;"><?php echo htmlspecialchars((string) ($payment['failure_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="background:#fff;padding:2rem;border-radius:8px;border:1px solid #eeeee4;">
                        <p class="eyebrow" style="color:var(--brand-pink);margin-bottom:1.5rem;">Order Items</p>
                        <?php if ($items === []): ?>
                            <p style="color:var(--brand-muted);font-style:italic;">No saved order items were found for this order.</p>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                                <?php foreach ($items as $item): ?>
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:1.5rem;border-bottom:1px solid #eeeee4;last-child:border:none;last-child:padding-bottom:0;">
                                        <div>
                                            <strong style="display:block;color:var(--brand-teal);font-size:1.1rem;margin-bottom:0.25rem;"><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <div style="font-size:0.85rem;color:var(--brand-muted);margin-bottom:0.5rem;"><?php echo htmlspecialchars((string) ($item['variant_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, qty <?php echo htmlspecialchars((string) ($item['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php foreach (($item['addons'] ?? []) as $addon): ?>
                                                <div style="font-size:0.85rem;color:var(--brand-muted);">+ <?php echo htmlspecialchars((string) ($addon['addon_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (x<?php echo htmlspecialchars((string) ($addon['quantity'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>) - $<?php echo htmlspecialchars(number_format((float) ($addon['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <strong style="color:var(--brand-ink);font-size:1.1rem;">$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <aside style="background:#fff;padding:2rem;border-radius:8px;border:1px solid #eeeee4;box-shadow:0 4px 15px rgba(0,0,0,0.02);position:sticky;top:1rem;">
                    <p class="eyebrow" style="color:var(--brand-pink);margin-bottom:0.5rem;">Order Summary</p>
                    <h2 style="color:var(--brand-teal);font-size:1.8rem;text-transform:uppercase;margin-bottom:1.5rem;">Status at a glance</h2>
                    
                    <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid #ddd;font-size:0.95rem;color:var(--brand-muted);">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span>Order Status</span>
                            <span style="display:inline-block;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:700;background:#eee;color:#333;text-transform:uppercase;"><?php echo htmlspecialchars((string) ($order['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['subtotal'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <?php if (!empty($order['promo_code']) && (float) ($order['promo_discount_amount'] ?? 0) > 0): ?>
                            <div style="display:flex;justify-content:space-between;color:var(--brand-pink);"><span>Promo (<?php echo htmlspecialchars((string) ($order['promo_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</span><strong>-$<?php echo htmlspecialchars(number_format((float) ($order['promo_discount_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <?php endif; ?>
                        <div style="display:flex;justify-content:space-between;"><span>Delivery Fee</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['delivery_fee'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <div style="display:flex;justify-content:space-between;"><span>Tax</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['tax_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <?php if ((float) ($order['tip_amount'] ?? 0) > 0): ?>
                            <div style="display:flex;justify-content:space-between;color:var(--brand-pink);"><span>Tip</span><strong>$<?php echo htmlspecialchars(number_format((float) ($order['tip_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:2.5rem;">
                        <span style="font-weight:700;color:var(--brand-teal);text-transform:uppercase;">Total</span>
                        <strong style="font-size:1.8rem;color:var(--brand-ink);">$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:1rem;">
                        <a href="/order-confirmation?<?php echo htmlspecialchars((string) http_build_query(['number' => (string) ($order['order_number'] ?? ''), 'token' => (string) ($accessToken ?? '')]), ENT_QUOTES, 'UTF-8'); ?>" class="btn" style="text-align:center;padding:1rem;">View Order Confirmation</a>
                        <a href="/" class="btn-secondary" style="text-align:center;padding:1rem;">Return Home</a>
                        <a href="/contact" class="btn-text" style="text-align:center;padding:1rem;">Contact Store</a>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>
