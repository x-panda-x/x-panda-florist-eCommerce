<?php
$customer = is_array($customer ?? null) ? $customer : [];
$formData = is_array($formData ?? null) ? $formData : [];
?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-card" style="max-width:700px;">
        <div class="account-card-header">
            <h2>Account Profile</h2>
            <p class="site-note">Update the core profile fields attached to your customer account.</p>
        </div>

        <form method="post" action="/account/profile" class="stack-lg">
            <?php echo csrf_field(); ?>
            <div class="field-grid cols-2">
                <div class="field-group" style="grid-column: 1 / -1;">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" type="text" required value="<?php echo htmlspecialchars((string) ($formData['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="field-group">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars((string) ($formData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="field-group">
                    <label for="phone">Phone Number</label>
                    <input id="phone" name="phone" type="text" value="<?php echo htmlspecialchars((string) ($formData['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn" style="min-width: 240px;">Save Profile</button>
            </div>
        </form>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
