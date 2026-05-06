<?php $formData = is_array($formData ?? null) ? $formData : []; ?>
<div class="account-content account-auth-shell account-auth-shell--narrow">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="account-auth-header">
        <h1 class="account-auth-title">Forgot Password</h1>
        <p class="site-note" style="margin:0;font-size:1.05rem;">Request a password reset link.</p>
    </div>

    <form method="post" action="/account/forgot-password" class="stack-lg">
        <?php echo csrf_field(); ?>
        <div class="field-group">
            <label for="email" style="font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.75rem;">Email Address</label>
            <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars((string) ($formData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.75rem;font-size:1rem;background:rgba(255,255,255,0.9);">
        </div>
        
        <button type="submit" class="btn" style="width:100%;margin-top:0.5rem;padding:0.9rem;font-size:0.9rem;">Send Reset Link</button>
        
        <p class="site-note account-auth-footer">
            Remembered your password? <a href="/account/login" style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.8rem;font-weight:600;text-decoration:none;border-bottom:1px solid var(--color-rose-line);padding-bottom:2px;margin-left:0.5rem;">Back to Sign In</a>
        </p>
    </form>
</div>
