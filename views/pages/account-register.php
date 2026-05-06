<?php $formData = is_array($formData ?? null) ? $formData : []; ?>
<?php $returnTo = trim((string) ($returnTo ?? '')); ?>
<div class="account-content account-auth-shell">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-auth-header">
        <h1 class="account-auth-title">Create Account</h1>
        <p class="site-note" style="margin:0;font-size:1.05rem;">Build your customer account foundation.</p>
        <?php if ($returnTo !== ''): ?>
            <p class="site-note" style="margin:0.75rem 0 0;">Create your account once and we will return you directly to checkout with your saved cart.</p>
        <?php endif; ?>
    </div>

    <form method="post" action="/account/register" class="stack-lg">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="field-grid cols-2">
            <div class="field-group">
                <label for="full_name" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Full Name</label>
                <input id="full_name" name="full_name" type="text" required value="<?php echo htmlspecialchars((string) ($formData['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
            <div class="field-group">
                <label for="phone" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Phone Number</label>
                <input id="phone" name="phone" type="text" value="<?php echo htmlspecialchars((string) ($formData['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
        </div>
        <div class="field-group">
            <label for="email" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Email Address</label>
            <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars((string) ($formData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
        </div>
        <div class="field-grid cols-2">
            <div class="field-group">
                <label for="password" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Password</label>
                <input id="password" name="password" type="password" required minlength="8" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
            <div class="field-group">
                <label for="password_confirmation" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
        </div>
        
        <button type="submit" class="btn" style="width:100%;margin-top:0.5rem;padding:0.9rem;font-size:0.9rem;">Create Account</button>
        
        <p class="site-note account-auth-footer">
            Already have an account? <a href="/account/login<?php echo $returnTo !== '' ? '?return_to=' . urlencode($returnTo) : ''; ?>" style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.8rem;font-weight:600;text-decoration:none;border-bottom:1px solid var(--color-rose-line);padding-bottom:2px;margin-left:0.5rem;">Sign In</a>
        </p>
    </form>
</div>
