<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Application;
use App\Core\Controller;
use App\Services\CustomerAuthService;

abstract class BaseAccountController extends Controller
{
    protected CustomerAuthService $authService;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->authService = new CustomerAuthService($app);
    }

    protected function requireCustomer(): void
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
        $_SESSION['_account_flash'][$key] = $message;
    }

    protected function consumeFlash(string $key): ?string
    {
        $message = $_SESSION['_account_flash'][$key] ?? null;

        if (is_string($message)) {
            unset($_SESSION['_account_flash'][$key]);

            return $message;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function renderStorefront(string $view, array $data = []): string
    {
        return $this->view($view, array_merge($data, [
            'customer' => $this->authService->customer(),
        ]), 'storefront');
    }
}
