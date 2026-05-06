<?php $customer = is_array($customer ?? null) ? $customer : []; ?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-card-header">
        <h2>Dashboard</h2>
        <p class="site-note">Overview of your account details and preferences.</p>
    </div>
    
    <div class="account-grid">
        <div class="detail-card">
            <h3 class="summary-label">Profile</h3>
            <p class="site-note" style="margin-bottom: 1.5rem;">Update your full name, email address, and phone number.</p>
            <a href="/account/profile" class="btn-outline">Edit Profile</a>
        </div>
        <div class="detail-card">
            <h3 class="summary-label">Password</h3>
            <p class="site-note" style="margin-bottom: 1.5rem;">Change your password with current-password verification.</p>
            <a href="/account/password" class="btn-outline">Change Password</a>
        </div>
        <div class="detail-card">
            <h3 class="summary-label">Email Preferences</h3>
            <p class="site-note" style="margin-bottom: 1.5rem;">Manage marketing, reminder, and order-related email flags.</p>
            <a href="/account/email-preferences" class="btn-outline">Email Preferences</a>
        </div>
        <div class="detail-card">
            <h3 class="summary-label">Orders</h3>
            <p class="site-note" style="margin-bottom: 1.5rem;">Review owned order history and account-safe order details.</p>
            <a href="/account/orders" class="btn-outline">My Orders</a>
        </div>
        <div class="detail-card">
            <h3 class="summary-label">Address Book</h3>
            <p class="site-note" style="margin-bottom: 1.5rem;">Manage saved delivery addresses and choose one default address.</p>
            <a href="/account/addresses" class="btn-outline">Address Book</a>
        </div>
        <div class="detail-card">
            <h3 class="summary-label">Reminders</h3>
            <p class="site-note" style="margin-bottom: 1.5rem;">Manage saved occasion reminders with active and inactive status.</p>
            <a href="/account/reminders" class="btn-outline">Reminders</a>
        </div>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
