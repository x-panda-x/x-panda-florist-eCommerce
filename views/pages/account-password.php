<?php $customer = is_array($customer ?? null) ? $customer : []; ?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-card" style="max-width: 600px;">
        <div class="account-card-header">
            <h2>Change Password</h2>
            <p class="site-note">Update your sign-in password. Minimum 8 characters.</p>
        </div>

        <form method="post" action="/account/password" class="stack-lg">
            <?php echo csrf_field(); ?>
            <div class="stack-md">
                <div class="field-group">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required>
                </div>
                <div class="field-group">
                    <label for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" required minlength="8">
                </div>
                <div class="field-group">
                    <label for="new_password_confirmation">Confirm New Password</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required minlength="8">
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn" style="min-width: 240px;">Update Password</button>
            </div>
        </form>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
