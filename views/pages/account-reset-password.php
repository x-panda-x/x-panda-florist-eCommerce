<div class="account-content account-auth-shell account-auth-shell--narrow">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-auth-header">
        <h1 class="account-auth-title">Reset Password</h1>
        <p class="site-note" style="margin:0;font-size:1.05rem;">Choose a new password.</p>
    </div>

    <?php if (!empty($tokenValid)): ?>
        <form method="post" action="/account/reset-password" class="stack-lg">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars((string) ($token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field-group">
                <label for="password" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">New Password</label>
                <input id="password" name="password" type="password" required minlength="8" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
            <div class="field-group">
                <label for="password_confirmation" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
            </div>
            
            <button type="submit" class="btn" style="width:100%;margin-top:0.5rem;padding:0.9rem;font-size:0.9rem;">Reset Password</button>
            
            <p class="site-note account-auth-footer">
                <a href="/account/login" style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.8rem;font-weight:600;text-decoration:none;border-bottom:1px solid var(--color-rose-line);padding-bottom:2px;">Back to Sign In</a>
            </p>
        </form>
    <?php else: ?>
        <div class="stack-md text-center">
            <p class="site-note flash flash-error" style="text-align:center;">This reset link is invalid or has expired.</p>
            <div style="margin-top:2rem;">
                <a href="/account/forgot-password" class="btn" style="width:100%;padding:0.9rem;">Request New Link</a>
                <p class="site-note account-auth-footer">
                    <a href="/account/login" style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.8rem;font-weight:600;text-decoration:none;border-bottom:1px solid var(--color-rose-line);padding-bottom:2px;">Back to Sign In</a>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
