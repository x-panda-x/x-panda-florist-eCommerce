<?php
$customer = is_array($customer ?? null) ? $customer : [];
$orders = is_array($orders ?? null) ? $orders : [];
$addresses = is_array($addresses ?? null) ? $addresses : [];
$reminders = is_array($reminders ?? null) ? $reminders : [];
?>

<div class="admin-card">
    <p class="admin-kicker">Customer Profile</p>
    <h2 class="admin-title"><?php echo htmlspecialchars((string) ($customer['full_name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <p class="admin-subtitle">Review account details, recent ecommerce activity, saved addresses, and reminder data tied to this customer account.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="admin-grid cols-3" style="margin-top:1rem;">
    <div class="admin-card">
        <p class="admin-kicker">Basic Info</p>
        <div class="stack-sm">
            <div><strong>Name:</strong> <?php echo htmlspecialchars((string) ($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars((string) (($customer['phone'] ?? '') !== null ? (string) ($customer['phone'] ?? '') : ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Created:</strong> <?php echo htmlspecialchars((string) ($customer['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Last Login:</strong> <?php echo htmlspecialchars((string) ($customer['last_login_at'] ?? 'Never'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Updated:</strong> <?php echo htmlspecialchars((string) ($customer['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Status:</strong> <span class="admin-status-pill"><?php echo !empty($customer['is_active']) ? 'active' : 'inactive'; ?></span></div>
        </div>
    </div>

    <div class="admin-card">
        <p class="admin-kicker">Counts</p>
        <div class="stack-sm">
            <div><strong>Orders:</strong> <?php echo htmlspecialchars((string) ((int) ($customer['order_count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Saved Addresses:</strong> <?php echo htmlspecialchars((string) ((int) ($customer['address_count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Reminders:</strong> <?php echo htmlspecialchars((string) ((int) ($customer['reminder_count'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Marketing Opt-In:</strong> <?php echo !empty($customer['marketing_opt_in']) ? 'Yes' : 'No'; ?></div>
            <div><strong>Reminder Emails:</strong> <?php echo !empty($customer['reminder_email_opt_in']) ? 'Yes' : 'No'; ?></div>
            <div><strong>Order Emails:</strong> <?php echo !empty($customer['order_email_opt_in']) ? 'Yes' : 'No'; ?></div>
        </div>
    </div>

    <div class="admin-card">
        <p class="admin-kicker">Account Status</p>
        <p class="admin-note">Use this to disable customer login safely without deleting account history or related ecommerce records.</p>
        <form method="post" action="/admin/customers/toggle-status" style="margin-top:1rem;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($customer['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="return_to" value="/admin/customers/view">
            <button type="submit" class="admin-button-secondary"><?php echo !empty($customer['is_active']) ? 'Disable Account' : 'Reactivate Account'; ?></button>
        </form>
        <div style="margin-top:1rem;">
            <a href="/admin/customers" class="admin-text-button">Back to Customers</a>
        </div>
    </div>
</div>

<div class="admin-card" style="margin-top:1rem;">
    <p class="admin-kicker">Orders</p>
    <h3 class="admin-title" style="font-size:1.25rem;">Order History</h3>
    <div class="admin-table-wrap" style="margin-top:1rem;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders === []): ?>
                    <tr><td colspan="6">No orders found for this customer.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars((string) ($order['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="admin-status-pill"><?php echo htmlspecialchars((string) (($order['tracking_status_label'] ?? '') !== '' ? $order['tracking_status_label'] : str_replace('_', ' ', (string) ($order['status'] ?? 'pending'))), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a href="/admin/orders/view?id=<?php echo urlencode((string) ($order['id'] ?? 0)); ?>" class="admin-text-button">Open Order</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-grid cols-2" style="margin-top:1rem;">
    <div class="admin-card">
        <p class="admin-kicker">Addresses</p>
        <h3 class="admin-title" style="font-size:1.25rem;">Saved Addresses</h3>
        <?php if ($addresses === []): ?>
            <p class="admin-note" style="margin-top:1rem;">No saved addresses for this customer.</p>
        <?php else: ?>
            <div class="stack-md" style="margin-top:1rem;">
                <?php foreach ($addresses as $address): ?>
                    <div class="admin-soft-card admin-card" style="padding:1rem;">
                        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;">
                            <strong><?php echo htmlspecialchars((string) ($address['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($address['is_default'])): ?>
                                <span class="admin-status-pill">default</span>
                            <?php endif; ?>
                        </div>
                        <div class="admin-note" style="margin-top:0.65rem;"><strong>Recipient:</strong> <?php echo htmlspecialchars((string) ($address['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="admin-note" style="margin-top:0.35rem;white-space:pre-line;"><?php echo htmlspecialchars((string) ($address['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="admin-note" style="margin-top:0.35rem;"><strong>ZIP:</strong> <?php echo htmlspecialchars((string) ($address['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if (!empty($address['delivery_instructions'])): ?>
                            <div class="admin-note" style="margin-top:0.35rem;"><strong>Instructions:</strong> <?php echo htmlspecialchars((string) ($address['delivery_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <p class="admin-kicker">Reminders</p>
        <h3 class="admin-title" style="font-size:1.25rem;">Saved Reminders</h3>
        <?php if ($reminders === []): ?>
            <p class="admin-note" style="margin-top:1rem;">No reminders for this customer.</p>
        <?php else: ?>
            <div class="stack-md" style="margin-top:1rem;">
                <?php foreach ($reminders as $reminder): ?>
                    <div class="admin-soft-card admin-card" style="padding:1rem;">
                        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;">
                            <strong><?php echo htmlspecialchars((string) ($reminder['occasion_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="admin-status-pill"><?php echo !empty($reminder['is_active']) ? 'active' : 'inactive'; ?></span>
                        </div>
                        <div class="admin-note" style="margin-top:0.65rem;"><strong>Recipient:</strong> <?php echo htmlspecialchars((string) ($reminder['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="admin-note" style="margin-top:0.35rem;"><strong>Date:</strong> <?php echo htmlspecialchars((string) ($reminder['reminder_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if (!empty($reminder['product_name'])): ?>
                            <div class="admin-note" style="margin-top:0.35rem;"><strong>Product:</strong> <?php echo htmlspecialchars((string) ($reminder['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($reminder['order_number'])): ?>
                            <div class="admin-note" style="margin-top:0.35rem;"><strong>Paid Order:</strong> <a href="/admin/orders/view?id=<?php echo urlencode((string) ($reminder['order_id'] ?? 0)); ?>" class="admin-text-button"><?php echo htmlspecialchars((string) ($reminder['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></div>
                        <?php endif; ?>
                        <?php if (!empty($reminder['note'])): ?>
                            <div class="admin-note" style="margin-top:0.35rem;white-space:pre-line;"><strong>Note:</strong> <?php echo htmlspecialchars((string) ($reminder['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($reminder['last_sent_at'])): ?>
                            <div class="admin-note" style="margin-top:0.35rem;"><strong>Last Sent:</strong> <?php echo htmlspecialchars((string) ($reminder['last_sent_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
