<?php

declare(strict_types=1);

namespace App\Core;

final class CSRF
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        self::startSession();

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        self::startSession();

        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($sessionToken) && hash_equals($sessionToken, $token);
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = dirname(__DIR__, 2) . '/storage/cache/sessions';

        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0775, true);
        }

        if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
            throw new \RuntimeException('Session storage is not writable.');
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_save_path($sessionPath);
        session_start();
    }
}
