<?php

declare(strict_types=1);

use App\Services\ThemeService;

if (!function_exists('theme_settings')) {
    function theme_settings(string $scope = 'storefront'): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof ThemeService) {
            $service = new ThemeService($application);
        }

        return $service->getThemeSettings($scope);
    }
}

if (!function_exists('theme_setting')) {
    function theme_setting(string $key, mixed $default = null, string $scope = 'storefront'): mixed
    {
        $settings = theme_settings($scope);

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }
}

if (!function_exists('theme_presets')) {
    function theme_presets(): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof ThemeService) {
            $service = new ThemeService($application);
        }

        return $service->listPresets();
    }
}

if (!function_exists('theme_css_variables')) {
    function theme_css_variables(): array
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return [];
        }

        if (!$service instanceof ThemeService) {
            $service = new ThemeService($application);
        }

        return $service->storefrontCssVariables();
    }
}
