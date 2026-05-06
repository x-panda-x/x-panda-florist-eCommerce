<?php 
$customer = is_array($customer ?? null) ? $customer : []; 
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isAccountActive($path, $current) {
    if ($path === '/account' && $current === '/account') return 'active';
    if ($path !== '/account' && strpos((string)$current, $path) === 0) return 'active';
    return '';
}

$accountNavItems = [
    '/account' => 'Dashboard',
    '/account/profile' => 'Profile',
    '/account/password' => 'Password',
    '/account/email-preferences' => 'Email Preferences',
    '/account/orders' => 'Orders',
    '/account/addresses' => 'Addresses',
    '/account/reminders' => 'Reminders',
];

$activeAccountLabel = 'Dashboard';
foreach ($accountNavItems as $path => $label) {
    if (isAccountActive($path, (string) $currentPath) === 'active') {
        $activeAccountLabel = $label;
        break;
    }
}
?>
<div class="container">
    <div class="account-wrap">
        <aside class="account-sidebar-box" data-account-nav-shell>
            <h1 class="account-welcome">My Account</h1>
            <p class="account-welcome-sub">Welcome back, <?php echo htmlspecialchars((string) ($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>.</p>

            <button type="button" class="account-nav-toggle" aria-expanded="false" aria-controls="accountSidebarPanel">
                <span class="account-nav-toggle__label">
                    <span class="account-nav-toggle__eyebrow">Account Menu</span>
                    <span class="account-nav-toggle__value"><?php echo htmlspecialchars($activeAccountLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <svg viewBox="0 0 20 20" width="18" height="18" fill="none" aria-hidden="true">
                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="account-sidebar-panel" id="accountSidebarPanel">
                <nav class="account-nav">
                    <a href="/account" class="account-nav-link <?php echo isAccountActive('/account', $currentPath); ?>">Dashboard</a>
                    <a href="/account/profile" class="account-nav-link <?php echo isAccountActive('/account/profile', $currentPath); ?>">Profile</a>
                    <a href="/account/password" class="account-nav-link <?php echo isAccountActive('/account/password', $currentPath); ?>">Password</a>
                    <a href="/account/email-preferences" class="account-nav-link <?php echo isAccountActive('/account/email-preferences', $currentPath); ?>">Email Preferences</a>
                    <a href="/account/orders" class="account-nav-link <?php echo isAccountActive('/account/orders', $currentPath); ?>">Orders</a>
                    <a href="/account/addresses" class="account-nav-link <?php echo isAccountActive('/account/addresses', $currentPath); ?>">Addresses</a>
                    <a href="/account/reminders" class="account-nav-link <?php echo isAccountActive('/account/reminders', $currentPath); ?>">Reminders</a>
                    
                    <form method="post" action="/account/logout" class="account-nav-logout">
                        <?php echo csrf_field(); ?>
                        <button type="submit">Log Out</button>
                    </form>
                </nav>
            </div>
        </aside>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const accountShell = document.querySelector('[data-account-nav-shell]');
    if (!accountShell) {
        return;
    }

    const toggle = accountShell.querySelector('.account-nav-toggle');
    const panel = accountShell.querySelector('.account-sidebar-panel');
    const accountContent = document.querySelector('.account-content');
    const accountLinks = Array.from(accountShell.querySelectorAll('.account-nav-link'));
    const smallLayoutMedia = window.matchMedia('(max-width: 1024px)');

    if (!toggle || !panel) {
        return;
    }

    function syncAccountNavMode() {
        if (!smallLayoutMedia.matches) {
            accountShell.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            panel.removeAttribute('hidden');
            return;
        }

        const isOpen = accountShell.classList.contains('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        panel.toggleAttribute('hidden', !isOpen);
    }

    function closeSmallLayoutNav() {
        if (!smallLayoutMedia.matches) {
            return;
        }

        accountShell.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        panel.setAttribute('hidden', 'hidden');
    }

    closeSmallLayoutNav();
    syncAccountNavMode();

    toggle.addEventListener('click', () => {
        if (!smallLayoutMedia.matches) {
            return;
        }

        const shouldOpen = !accountShell.classList.contains('is-open');
        accountShell.classList.toggle('is-open', shouldOpen);
        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        panel.toggleAttribute('hidden', !shouldOpen);

        if (!shouldOpen && accountContent) {
            accountContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    accountLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (!smallLayoutMedia.matches) {
                return;
            }

            sessionStorage.setItem('accountNavScrollToContent', '1');
            closeSmallLayoutNav();
        });
    });

    if (smallLayoutMedia.matches && sessionStorage.getItem('accountNavScrollToContent') === '1') {
        sessionStorage.removeItem('accountNavScrollToContent');
        window.requestAnimationFrame(() => {
            if (!accountContent) {
                return;
            }

            const top = accountContent.getBoundingClientRect().top;
            if (top > window.innerHeight * 0.2) {
                accountContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    const handleMediaChange = () => {
        if (smallLayoutMedia.matches) {
            closeSmallLayoutNav();
        } else {
            accountShell.classList.add('is-open');
            panel.removeAttribute('hidden');
        }
        syncAccountNavMode();
    };

    if (typeof smallLayoutMedia.addEventListener === 'function') {
        smallLayoutMedia.addEventListener('change', handleMediaChange);
    } else if (typeof smallLayoutMedia.addListener === 'function') {
        smallLayoutMedia.addListener(handleMediaChange);
    }
});
</script>
