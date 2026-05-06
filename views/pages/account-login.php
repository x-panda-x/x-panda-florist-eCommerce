<?php $formData = is_array($formData ?? null) ? $formData : []; ?>
<?php $returnTo = trim((string) ($returnTo ?? '')); ?>
<div class="account-content account-auth-shell account-auth-shell--narrow">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-auth-header">
        <h1 class="account-auth-title">Sign In</h1>
        <p class="site-note" style="margin:0;font-size:1.05rem;">Access your florist account.</p>
        <?php if ($returnTo !== ''): ?>
            <p class="site-note" style="margin:0.75rem 0 0;">Sign in to return directly to checkout with your saved cart.</p>
        <?php endif; ?>
    </div>

    <form method="post" action="/account/login" class="stack-lg">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="stack-md">
            <div class="field-group">
                <label for="email" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Email Address</label>
                <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars((string) ($formData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
            <div class="field-group">
                <div class="account-auth-inline">
                    <label for="password" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Password</label>
                    <a href="/account/forgot-password" class="site-note" style="font-size:0.8rem;color:var(--color-gray-dark);text-decoration:underline;">Forgot password?</a>
                </div>
                <input id="password" name="password" type="password" required style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
        </div>
        
        <button type="submit" class="btn" style="width:100%;margin-top:0.5rem;padding:0.9rem;font-size:0.9rem;">Sign In</button>
        
        <p class="site-note account-auth-footer">
            Don't have an account? <a href="/account/register<?php echo $returnTo !== '' ? '?return_to=' . urlencode($returnTo) : ''; ?>" style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.8rem;font-weight:600;text-decoration:none;border-bottom:1px solid var(--color-rose-line);padding-bottom:2px;margin-left:0.5rem;">Create Account</a>
        </p>
    </form>
</div>
