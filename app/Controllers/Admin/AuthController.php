<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;

final class AuthController extends BaseAdminController
{
    public function showLogin(): string
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/admin');
        }

        return $this->view('admin-login', [
            'error' => $this->loginErrorMessage(),
            'pageTitle' => 'Admin Login',
            'showLogoutLink' => false,
        ], 'admin');
    }

    public function login(): string
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->redirect('/admin/login?error=csrf');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!$this->authService->attemptLogin($email, $password)) {
            $this->redirect('/admin/login?error=credentials');
        }

        $this->redirect('/admin');
    }

    public function logout(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin');
        }

        $this->authService->logout();
        $this->redirect('/admin/login');
    }

    public function dashboard(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-dashboard', [
            'pageTitle' => 'Admin Dashboard',
        ]);
    }

    private function loginErrorMessage(): ?string
    {
        $error = (string) ($_GET['error'] ?? '');

        return match ($error) {
            'csrf' => 'The form session expired. Please try again.',
            'credentials' => 'Invalid email or password.',
            default => null,
        };
    }
}
