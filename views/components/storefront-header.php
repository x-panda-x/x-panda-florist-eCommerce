<?php
App\Core\CSRF::token();
$storeName = (string) settings('store_name', 'Lily and Rose');
$storePhone = (string) settings('store_phone', 'Call the studio');
$storeCutoff = (string) settings('same_day_cutoff', 'Today');
$storeAddress = trim((string) settings('store_address', ''));
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$customerLoggedIn = ($_SESSION['customer_logged_in'] ?? false) === true && !empty($_SESSION['customer_id']);
$customerName = trim((string) ($_SESSION['customer_name'] ?? ''));
$managedMenu = navigation_menu('storefront-primary');
$fallbackMenu = [
    [
        'label' => 'Home',
        'url' => '/',
    ],
    [
        'label' => 'Best Sellers',
        'url' => '/best-sellers',
    ],
    [
        'label' => 'Occasions',
        'url' => '/occasions',
    ],
    [
        'label' => 'Search',
        'url' => '/search',
    ],
    [
        'label' => 'Order Status',
        'url' => '/order-status',
    ],
    [
        'url' => '/contact',
        'label' => 'Contact',
    ],
];
$primaryMenuItems = is_array($managedMenu['items'] ?? null) && ($managedMenu['items'] ?? []) !== []
    ? $managedMenu['items']
    : $fallbackMenu;

?>
<header class="site-header">
    <div class="header-top">
        <div class="container header-top-container">
            <div class="header-left">
                <button type="button" class="mobile-menu-toggle icon-button" aria-label="Toggle Navigation" aria-controls="mobileNavDrawer" aria-expanded="false">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <svg viewBox="0 0 24 24" class="icon-pin" width="14" height="14" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <span><?php echo htmlspecialchars($storeAddress !== '' ? $storeAddress : $storePhone, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="header-center">
                <a href="/" class="header-logo-box">
                    <span class="logo-text"><?php echo htmlspecialchars(strtoupper($storeName), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <div class="logo-subtext">Same-day cutoff: <?php echo htmlspecialchars($storeCutoff !== '' ? $storeCutoff : 'Posted at checkout', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="header-right">
                <?php if ($customerLoggedIn): ?>
                    <span class="loyalty-text"><?php echo htmlspecialchars($customerName !== '' ? $customerName : 'Account', ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <a href="/account/login" class="loyalty-text" style="text-decoration:none;">Sign In</a>
                <?php endif; ?>
                <form method="get" action="/search" class="header-search-form">
                    <input name="q" type="search" aria-label="Search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>" placeholder="SEARCH" class="header-search-input">
                    <button type="submit" class="icon-button">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </button>
                </form>

                <?php if ($customerLoggedIn): ?>
                    <a href="/account" class="icon-button" aria-label="Account">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    </a>
                <?php else: ?>
                    <a href="/account/login" class="icon-button" aria-label="Sign In">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    </a>
                <?php endif; ?>

                <a href="/cart" class="icon-button" aria-label="Cart">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 9V7.75C8 5.68 9.68 4 11.75 4h.5C14.32 4 16 5.68 16 7.75V9"/>
                        <path d="M6.25 9H17.75L19 20H5L6.25 9Z"/>
                        <path d="M9.5 12.5V13.25"/>
                        <path d="M14.5 12.5V13.25"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="mobile-backdrop"></div>
    <nav class="nav-bar" id="mobileNavDrawer" aria-hidden="true">
        <button type="button" class="mobile-menu-close icon-button" aria-label="Close Navigation">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <div class="container container-nav">
            <div class="mobile-menu-meta">
                <p class="mobile-menu-meta__eyebrow">Browse the studio</p>
                <div class="mobile-menu-meta__links">
                    <a href="/search">Search</a>
                    <?php if ($customerLoggedIn): ?>
                        <a href="/account">My Account</a>
                    <?php else: ?>
                        <a href="/account/login">Sign In</a>
                        <a href="/account/register">Create Account</a>
                    <?php endif; ?>
                </div>
            </div>
            <ul class="nav-links">
                <?php foreach ($primaryMenuItems as $menuItem): ?>
                    <?php $hasChildren = !empty($menuItem['children']) && is_array($menuItem['children']); ?>
                    <?php $dropdownStyle = (string) ($menuItem['display_style'] ?? 'list'); ?>
                    <?php $dropdownGroups = is_array($menuItem['dropdown_groups'] ?? null) ? $menuItem['dropdown_groups'] : []; ?>
                    <?php $useMegaDropdown = $hasChildren && !empty($menuItem['is_mega_menu']) && $dropdownGroups !== []; ?>
                    <li class="nav-item <?php echo $hasChildren ? 'has-dropdown' : ''; ?> <?php echo $useMegaDropdown ? 'has-mega-dropdown' : ''; ?>" data-dropdown-style="<?php echo htmlspecialchars($dropdownStyle, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="nav-item__top">
                            <a class="nav-item__link" href="<?php echo htmlspecialchars((string) ($menuItem['url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $hasChildren ? 'aria-haspopup="true"' : ''; ?> <?php echo (string) ($menuItem['target'] ?? '') === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> data-dropdown-style="<?php echo htmlspecialchars($dropdownStyle, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars((string) ($menuItem['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <?php if ($hasChildren): ?>
                                <button type="button" class="nav-item__toggle" aria-label="Toggle <?php echo htmlspecialchars((string) ($menuItem['label'] ?? 'submenu'), ENT_QUOTES, 'UTF-8'); ?>" aria-expanded="false">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" style="stroke:currentColor; stroke-width:1.5; stroke-linecap:round;"><path d="M1 1L5 5L9 1"/></svg>
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($hasChildren): ?>
                            <div class="nav-dropdown <?php echo $useMegaDropdown ? 'nav-dropdown--mega' : ''; ?>" data-dropdown-style="<?php echo htmlspecialchars($dropdownStyle, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if ($useMegaDropdown): ?>
                                    <?php foreach ($dropdownGroups as $group): ?>
                                        <div class="nav-dropdown-group" data-column-key="<?php echo htmlspecialchars((string) ($group['column_key'] ?? 'default'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php if (!empty($group['title'])): ?>
                                                <p class="nav-dropdown-group__title"><?php echo htmlspecialchars((string) $group['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                            <?php foreach (($group['items'] ?? []) as $child): ?>
                                                <a href="<?php echo htmlspecialchars((string) ($child['url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($child['target'] ?? '') === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                                    <?php echo htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($menuItem['children'] as $child): ?>
                                        <a href="<?php echo htmlspecialchars((string) ($child['url'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($child['target'] ?? '') === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                            <?php echo htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    const closeBtn = document.querySelector('.mobile-menu-close');
    const navDrawer = document.getElementById('mobileNavDrawer');
    const backdrop = document.querySelector('.mobile-backdrop');
    const navItems = Array.from(document.querySelectorAll('.nav-item.has-dropdown'));
    const desktopHoverMedia = window.matchMedia('(min-width: 1025px) and (hover: hover) and (pointer: fine)');
    let activeDesktopItem = null;
    let closeTimer = null;

    function isDesktopHoverNav() {
        return desktopHoverMedia.matches;
    }

    function resetAccordionState() {
        navItems.forEach((item) => {
            item.classList.remove('is-active');
            const toggle = item.querySelector('.nav-item__toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function resetDesktopState() {
        navItems.forEach((item) => item.classList.remove('is-hover-open'));
        activeDesktopItem = null;
    }

    function syncDrawerState(isOpen) {
        if (!navDrawer || !backdrop || !toggleBtn) {
            return;
        }

        navDrawer.classList.toggle('is-open', isOpen);
        backdrop.classList.toggle('is-open', isOpen);
        navDrawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('mobile-nav-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function syncResponsiveNavMode() {
        if (!navDrawer || !backdrop || !toggleBtn) {
            return;
        }

        if (isDesktopHoverNav()) {
            navDrawer.classList.remove('is-open');
            navDrawer.setAttribute('aria-hidden', 'false');
            backdrop.classList.remove('is-open');
            toggleBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('mobile-nav-open');
            document.body.style.overflow = '';
            resetAccordionState();
            return;
        }

        const isOpen = navDrawer.classList.contains('is-open');
        navDrawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        backdrop.classList.toggle('is-open', isOpen);
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('mobile-nav-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
    
    function closeMenu() {
        syncDrawerState(false);
        resetAccordionState();
    }

    function syncDesktopState(item, isOpen) {
        item.classList.toggle('is-hover-open', isOpen);
    }

    function queueDesktopClose(item) {
        clearTimeout(closeTimer);
        closeTimer = window.setTimeout(() => {
            syncDesktopState(item, false);
            if (activeDesktopItem === item) {
                activeDesktopItem = null;
            }
        }, 180);
    }

    function openDesktopItem(item) {
        clearTimeout(closeTimer);
        if (activeDesktopItem && activeDesktopItem !== item) {
            syncDesktopState(activeDesktopItem, false);
        }
        syncDesktopState(item, true);
        activeDesktopItem = item;
    }

    if(toggleBtn && navDrawer && backdrop) {
        toggleBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (isDesktopHoverNav()) {
                return;
            }

            const shouldOpen = !navDrawer.classList.contains('is-open');
            syncDrawerState(shouldOpen);
            if (!shouldOpen) {
                resetAccordionState();
            }
        });

        closeBtn.addEventListener('click', closeMenu);
        backdrop.addEventListener('click', closeMenu);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    }

    navItems.forEach((item) => {
        const toggle = item.querySelector('.nav-item__toggle');
        const dropdown = item.querySelector('.nav-dropdown');

        if (!toggle || !dropdown) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();

            event.stopPropagation();

            if (!isDesktopHoverNav()) {
                const isOpen = !item.classList.contains('is-active');
                resetAccordionState();
                item.classList.toggle('is-active', isOpen);
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                return;
            }
        });

        item.addEventListener('mouseenter', () => {
            if (isDesktopHoverNav()) {
                openDesktopItem(item);
            }
        });

        item.addEventListener('mouseleave', () => {
            if (isDesktopHoverNav()) {
                queueDesktopClose(item);
            }
        });

        dropdown.addEventListener('mouseenter', () => {
            if (isDesktopHoverNav()) {
                openDesktopItem(item);
            }
        });

        dropdown.addEventListener('mouseleave', () => {
            if (isDesktopHoverNav()) {
                queueDesktopClose(item);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!isDesktopHoverNav()) {
            return;
        }

        if (activeDesktopItem && !event.target.closest('.nav-item.has-dropdown')) {
            clearTimeout(closeTimer);
            syncDesktopState(activeDesktopItem, false);
            activeDesktopItem = null;
        }
    });

    syncResponsiveNavMode();

    window.addEventListener('resize', () => {
        if (isDesktopHoverNav()) {
            closeMenu();
            resetDesktopState();
            syncResponsiveNavMode();
            return;
        }

        resetDesktopState();
        syncResponsiveNavMode();
    });
});
</script>
