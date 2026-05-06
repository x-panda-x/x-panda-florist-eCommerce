<?php

declare(strict_types=1);

use App\Services\SettingsService;

if (!function_exists('settings')) {
    function settings(string $key, mixed $default = null): mixed
    {
        global $application;

        static $service = null;

        if (!$application instanceof App\Core\Application) {
            return $default;
        }

        if (!$service instanceof SettingsService) {
            $service = new SettingsService($application);
        }

        return $service->get($key, $default);
    }
}

if (!function_exists('app_config')) {
    function app_config(string $key, mixed $default = null): mixed
    {
        static $config = null;

        if (!is_array($config)) {
            $loaded = require BASE_PATH . '/config/app.php';
            $config = is_array($loaded) ? $loaded : [];
        }

        return array_key_exists($key, $config) ? $config[$key] : $default;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $configured = trim((string) settings('public_base_url', ''));

        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL) !== false) {
            return rtrim($configured, '/');
        }

        $configUrl = trim((string) app_config('url', ''));

        if ($configUrl !== '' && filter_var($configUrl, FILTER_VALIDATE_URL) !== false) {
            return rtrim($configUrl, '/');
        }

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

        if ($host !== '') {
            $forwardedProto = trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            $https = trim((string) ($_SERVER['HTTPS'] ?? ''));
            $isSecure = $forwardedProto === 'https' || ($https !== '' && strtolower($https) !== 'off');

            return ($isSecure ? 'https://' : 'http://') . $host;
        }

        return 'http://127.0.0.1:8091';
    }
}

if (!function_exists('public_url')) {
    /**
     * @param array<string, scalar|null> $query
     */
    function public_url(string $path = '', array $query = []): string
    {
        $normalizedPath = '/' . ltrim($path, '/');
        $url = app_base_url();

        if ($normalizedPath !== '/') {
            $url .= $normalizedPath;
        }

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}
