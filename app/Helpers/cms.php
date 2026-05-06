<?php

declare(strict_types=1);

use App\Services\CMSService;
use App\Services\NavigationService;

if (!function_exists('cms_block')) {
    function cms_block(string $blockKey, bool $includeItems = false): ?array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return null;
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->findBlockByKey($blockKey, $includeItems);
    }
}

if (!function_exists('cms_blocks')) {
    function cms_blocks(string $pageKey, bool $enabledOnly = true, bool $includeItems = false): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->listBlocksByPage($pageKey, $enabledOnly, $includeItems);
    }
}

if (!function_exists('cms_block_items')) {
    function cms_block_items(int $contentBlockId, bool $enabledOnly = true): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->listBlockItems($contentBlockId, $enabledOnly);
    }
}

if (!function_exists('cms_banner')) {
    function cms_banner(string $bannerKey, bool $enabledOnly = true): ?array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return null;
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->findBannerByKey($bannerKey, $enabledOnly);
    }
}

if (!function_exists('cms_banners')) {
    function cms_banners(string $pageKey, ?string $placement = null, bool $enabledOnly = true): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->listBanners($pageKey, $placement, $enabledOnly);
    }
}

if (!function_exists('cms_banner_for_placement')) {
    function cms_banner_for_placement(string $placement, string $pageKey = 'global'): ?array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return null;
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->activeBannerForPlacement($placement, $pageKey);
    }
}

if (!function_exists('footer_blocks')) {
    function footer_blocks(bool $enabledOnly = true): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->getFooterBlocks($enabledOnly);
    }
}

if (!function_exists('homepage_blocks')) {
    function homepage_blocks(bool $enabledOnly = true): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->getHomepageBlocks($enabledOnly);
    }
}

if (!function_exists('public_page_blocks')) {
    function public_page_blocks(bool $enabledOnly = true): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof CMSService) {
            $service = new CMSService($application);
        }

        return $service->getPublicPageBlocks($enabledOnly);
    }
}

if (!function_exists('navigation_menu')) {
    function navigation_menu(string $menuKey = 'storefront-primary', bool $enabledOnly = true): ?array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return null;
        }

        if (!$service instanceof NavigationService) {
            $service = new NavigationService($application);
        }

        if ($menuKey === 'storefront-primary') {
            $menu = $service->getPrimaryStorefrontMenu($enabledOnly, true);

            return $menu !== [] ? $menu : null;
        }

        return $service->findMenuByKey($menuKey, $enabledOnly, true);
    }
}
