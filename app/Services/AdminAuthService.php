<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class AdminAuthService
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->startSession();
    }

    public function attemptLogin(string $email, string $password): bool
    {
        if ($email === '' || $password === '') {
            return false;
        }

        $admin = $this->app->database()->query(
            'SELECT id, email, password_hash FROM admins WHERE email = :email AND is_active = 1 LIMIT 1',
            ['email' => $email]
        )->fetch();

        if (!is_array($admin) || !password_verify($password, (string) $admin['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_email'] = (string) $admin['email'];
        $_SESSION['admin_logged_in'] = true;

        return true;
    }

    public function isLoggedIn(): bool
    {
        return ($_SESSION['admin_logged_in'] ?? false) === true
            && isset($_SESSION['admin_id'], $_SESSION['admin_email']);
    }

    public function requireAuth(): void
    {
        if ($this->isLoggedIn()) {
            return;
        }

        header('Location: /admin/login');
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function adminEmail(): ?string
    {
        return isset($_SESSION['admin_email']) ? (string) $_SESSION['admin_email'] : null;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = $this->app->getBasePath('storage/cache/sessions');

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
