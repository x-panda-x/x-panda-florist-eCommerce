<?php
$customer = is_array($customer ?? null) ? $customer : [];
$orders = is_array($orders ?? null) ? $orders : [];
?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <div class="account-card">
        <div class="account-card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2>Order History</h2>
                <p class="site-note">Review your recent and past orders.</p>
            </div>
            <a href="/search" class="btn" style="padding:0.6rem 1.5rem;">Shop Now</a>
        </div>

        <?php if ($orders === []): ?>
            <div class="empty-state">
                <p class="eyebrow">No Orders Yet</p>
                <h3 class="serif-head" style="margin-bottom:1rem;font-size:1.5rem;">You haven't placed any orders yet.</h3>
                <p class="site-note">Once you place an order, it will appear here.</p>
            </div>
        <?php else: ?>
            <div class="stack-md">
                <?php foreach ($orders as $order): ?>
                    <div class="detail-card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.5rem;">
                        <div class="stack-sm">
                            <strong style="font-size:1.1rem;color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.05em;"><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="site-note" style="color:var(--color-gray-dark);">Placed <?php echo htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="site-note"><strong>Recipient:</strong> <?php echo htmlspecialchars((string) ($order['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="site-note"><strong>Delivery:</strong> <?php echo htmlspecialchars((string) ($order['delivery_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($order['delivery_time_slot'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="site-note" style="margin-top:0.5rem;">
                                <span class="status-pill"><?php echo htmlspecialchars((string) (($order['tracking_status_label'] ?? '') !== '' ? $order['tracking_status_label'] : ucwords(str_replace('_', ' ', (string) ($order['status'] ?? 'pending')))), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:1rem;text-align:right;">
                            <strong style="font-size:1.4rem;color:var(--color-black);">$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <a href="/account/orders/view?id=<?php echo urlencode((string) ($order['id'] ?? 0)); ?>" class="btn-secondary" style="font-size:0.8rem;padding:0.6rem 1.25rem;">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
