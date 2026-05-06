<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Controller;
use App\Services\AdminAuthService;

abstract class BaseAdminController extends Controller
{
    protected AdminAuthService $authService;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->authService = new AdminAuthService($app);
    }

    protected function requireAdmin(): void
    {
        $this->authService->requireAuth();
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    protected function consumeFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;

        if (is_string($message)) {
            unset($_SESSION['_flash'][$key]);

            return $message;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function renderAdmin(string $view, array $data = []): string
    {
        return $this->view($view, array_merge($data, [
            'adminEmail' => $this->authService->adminEmail(),
            'showLogoutLink' => true,
        ]), 'admin');
    }
}
