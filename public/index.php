<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $publicFile = BASE_PATH . '/public' . str_replace(['../', '..\\'], '', (string) $requestPath);

    if (is_file($publicFile)) {
        return false;
    }
}

sendSecurityHeaders();

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Helpers/csrf.php';
require BASE_PATH . '/app/Helpers/settings.php';
require BASE_PATH . '/app/Helpers/cms.php';
require BASE_PATH . '/app/Helpers/theme.php';

ensureWritableDirectory(BASE_PATH . '/storage/cache/sessions');
ensureWritableDirectory(BASE_PATH . '/storage/logs');

try {
    $application = new App\Core\Application(BASE_PATH);
    $application->boot();
} catch (\Throwable $exception) {
    error_log((string) $exception);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    echo 'Application error. Please try again later.';
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function ensureWritableDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Required runtime directory is missing: ' . $path);
    }

    if (!is_writable($path)) {
        throw new RuntimeException('Required runtime directory is not writable: ' . $path);
    }
}
