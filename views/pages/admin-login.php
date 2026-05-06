<div class="admin-login-shell">
    <div class="admin-form-shell">
        <p class="admin-kicker">Admin Login</p>
        <h2 class="admin-title">Sign in to manage the active florist runtime.</h2>
        <p class="admin-subtitle">This phase upgrades the login presentation only. Authentication flow and CSRF handling remain unchanged.</p>

        <?php if (!empty($error)): ?>
            <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/login" style="margin-top:1rem;">
            <?php echo csrf_field(); ?>
            <div class="admin-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <div class="admin-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button type="submit" class="admin-button" style="width:100%;">Sign In</button>
        </form>
    </div>
</div>
