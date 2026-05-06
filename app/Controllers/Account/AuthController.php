<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\CSRF;
use App\Services\CustomerPasswordResetService;

final class AuthController extends BaseAccountController
{
    private CustomerPasswordResetService $passwordResetService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->passwordResetService = new CustomerPasswordResetService($app);
    }

    public function showLogin(): string
    {
        $returnTo = $this->sanitizeReturnTo((string) ($_GET['return_to'] ?? ''));

        if ($this->authService->isLoggedIn()) {
            $this->redirect($returnTo !== '' ? $returnTo : '/account');
        }

        $oldInput = $_SESSION['_account_login_old'] ?? [];
        unset($_SESSION['_account_login_old']);

        return $this->renderStorefront('account-login', [
            'pageTitle' => 'Customer Login',
            'error' => $this->consumeFlash('login_error'),
            'success' => $this->consumeFlash('login_success'),
            'formData' => is_array($oldInput) ? $oldInput : [],
            'returnTo' => $returnTo !== '' ? $returnTo : $this->sanitizeReturnTo((string) (($oldInput['return_to'] ?? ''))),
        ]);
    }

    public function login(): string
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('login_error', 'The form session expired. Please try again.');
            $this->redirect('/account/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $returnTo = $this->sanitizeReturnTo((string) ($_POST['return_to'] ?? ''));
        $_SESSION['_account_login_old'] = ['email' => $email, 'return_to' => $returnTo];
        $result = $this->authService->attemptLogin($email, (string) ($_POST['password'] ?? ''));

        if (!$result['success']) {
            $this->flash('login_error', (string) ($result['error'] ?? 'Unable to sign in.'));
            $this->redirect($this->buildAuthPath('/account/login', $returnTo));
        }

        unset($_SESSION['_account_login_old']);
        $this->flash('dashboard_success', 'You are signed in.');
        $this->redirect($returnTo !== '' ? $returnTo : '/account');
    }

    public function showRegister(): string
    {
        $returnTo = $this->sanitizeReturnTo((string) ($_GET['return_to'] ?? ''));

        if ($this->authService->isLoggedIn()) {
            $this->redirect($returnTo !== '' ? $returnTo : '/account');
        }

        $oldInput = $_SESSION['_account_register_old'] ?? [];
        unset($_SESSION['_account_register_old']);

        return $this->renderStorefront('account-register', [
            'pageTitle' => 'Create Account',
            'error' => $this->consumeFlash('register_error'),
            'success' => $this->consumeFlash('register_success'),
            'formData' => is_array($oldInput) ? $oldInput : [],
            'returnTo' => $returnTo !== '' ? $returnTo : $this->sanitizeReturnTo((string) (($oldInput['return_to'] ?? ''))),
        ]);
    }

    public function register(): string
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('register_error', 'The form session expired. Please try again.');
            $this->redirect('/account/register');
        }

        $_SESSION['_account_register_old'] = [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'return_to' => $this->sanitizeReturnTo((string) ($_POST['return_to'] ?? '')),
        ];

        $result = $this->authService->register($_POST);
        $returnTo = $this->sanitizeReturnTo((string) ($_POST['return_to'] ?? ''));

        if (!$result['success']) {
            $this->flash('register_error', (string) ($result['error'] ?? 'Unable to create the account.'));
            $this->redirect($this->buildAuthPath('/account/register', $returnTo));
        }

        unset($_SESSION['_account_register_old']);
        $this->flash('dashboard_success', 'Your account is ready.');
        $this->redirect($returnTo !== '' ? $returnTo : '/account');
    }

    public function logout(): string
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('login_error', 'The form session expired. Please try again.');
            $this->redirect('/account/login');
        }

        $this->authService->logout();
        $this->flash('login_success', 'You have been signed out.');
        $this->redirect('/account/login');
    }

    public function showForgotPassword(): string
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/account');
        }

        $oldInput = $_SESSION['_account_forgot_old'] ?? [];
        unset($_SESSION['_account_forgot_old']);

        return $this->renderStorefront('account-forgot-password', [
            'pageTitle' => 'Forgot Password',
            'error' => $this->consumeFlash('forgot_error'),
            'success' => $this->consumeFlash('forgot_success'),
            'formData' => is_array($oldInput) ? $oldInput : [],
        ]);
    }

    public function forgotPassword(): string
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('forgot_error', 'The form session expired. Please try again.');
            $this->redirect('/account/forgot-password');
        }

        $_SESSION['_account_forgot_old'] = [
            'email' => trim((string) ($_POST['email'] ?? '')),
        ];

        $result = $this->passwordResetService->requestReset((string) ($_POST['email'] ?? ''));
        unset($_SESSION['_account_forgot_old']);
        $this->flash('forgot_success', $result['message']);
        $this->redirect('/account/forgot-password');
    }

    public function showResetPassword(): string
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/account');
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $validation = $this->passwordResetService->validateToken($token);

        return $this->renderStorefront('account-reset-password', [
            'pageTitle' => 'Reset Password',
            'error' => $this->consumeFlash('reset_error') ?? (!$validation['valid'] ? $validation['error'] : null),
            'success' => $this->consumeFlash('reset_success'),
            'token' => $token,
            'tokenValid' => $validation['valid'],
        ]);
    }

    public function resetPassword(): string
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('reset_error', 'The form session expired. Please try again.');
            $this->redirect('/account/reset-password?token=' . urlencode((string) ($_POST['token'] ?? '')));
        }

        $token = trim((string) ($_POST['token'] ?? ''));
        $result = $this->passwordResetService->resetPassword(
            $token,
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password_confirmation'] ?? '')
        );

        if (!$result['success']) {
            $this->flash('reset_error', (string) ($result['error'] ?? 'Unable to reset the password.'));
            $this->redirect('/account/reset-password?token=' . urlencode($token));
        }

        $this->flash('login_success', 'Your password has been reset. Please sign in with the new password.');
        $this->redirect('/account/login');
    }

    private function sanitizeReturnTo(string $path): string
    {
        $path = trim($path);

        if ($path === '' || str_starts_with($path, '//') || preg_match('#^https?://#i', $path) === 1) {
            return '';
        }

        return str_starts_with($path, '/') ? $path : '';
    }

    private function buildAuthPath(string $basePath, string $returnTo): string
    {
        if ($returnTo === '') {
            return $basePath;
        }

        return $basePath . '?' . http_build_query(['return_to' => $returnTo]);
    }
}
