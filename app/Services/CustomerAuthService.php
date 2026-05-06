<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class CustomerAuthService
{
    private const SESSION_ID_KEY = 'customer_id';
    private const SESSION_EMAIL_KEY = 'customer_email';
    private const SESSION_NAME_KEY = 'customer_name';
    private const SESSION_LOGGED_IN_KEY = 'customer_logged_in';

    private Application $app;
    private CustomerService $customerService;
    private NotificationService $notificationService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->customerService = new CustomerService($app);
        $this->notificationService = new NotificationService($app);
        $this->startSession();
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, customer: array<string, mixed>|null}
     */
    public function register(array $input): array
    {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $email = $this->customerService->normalizeEmail((string) ($input['email'] ?? ''));
        $phone = $this->customerService->normalizePhone((string) ($input['phone'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $passwordConfirmation = (string) ($input['password_confirmation'] ?? '');

        if ($fullName === '') {
            return ['success' => false, 'error' => 'Full name is required.', 'customer' => null];
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'A valid email address is required.', 'customer' => null];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters.', 'customer' => null];
        }

        if (!hash_equals($password, $passwordConfirmation)) {
            return ['success' => false, 'error' => 'Password confirmation does not match.', 'customer' => null];
        }

        if ($this->customerService->findByEmail($email) !== null) {
            return ['success' => false, 'error' => 'An account already exists for that email address.', 'customer' => null];
        }

        $customerId = $this->customerService->create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'phone' => $phone,
            'is_active' => 1,
            'marketing_opt_in' => 0,
            'reminder_email_opt_in' => 1,
            'order_email_opt_in' => 1,
        ]);

        $customer = $this->customerService->findById($customerId);

        if ($customer === null) {
            return ['success' => false, 'error' => 'Unable to create the account right now.', 'customer' => null];
        }

        $this->loginCustomer($customer);

        try {
            $this->notificationService->sendCustomerWelcomeNotification($customer);
        } catch (\Throwable $exception) {
            // Registration should still succeed even if outbound email delivery fails.
        }

        return ['success' => true, 'error' => null, 'customer' => $customer];
    }

    /**
     * @return array{success: bool, error: string|null, customer: array<string, mixed>|null}
     */
    public function attemptLogin(string $email, string $password): array
    {
        $customer = $this->customerService->findByEmail($email);

        if ($customer === null || $password === '' || !password_verify($password, (string) ($customer['password_hash'] ?? ''))) {
            return ['success' => false, 'error' => 'Invalid email or password.', 'customer' => null];
        }

        if (($customer['is_active'] ?? false) !== true) {
            return ['success' => false, 'error' => 'This account is currently inactive.', 'customer' => null];
        }

        $this->loginCustomer($customer);
        $this->customerService->updateLastLoginAt((int) ($customer['id'] ?? 0));
        $customer = $this->customerService->findById((int) ($customer['id'] ?? 0));

        return ['success' => true, 'error' => null, 'customer' => $customer];
    }

    public function isLoggedIn(): bool
    {
        return ($_SESSION[self::SESSION_LOGGED_IN_KEY] ?? false) === true
            && isset($_SESSION[self::SESSION_ID_KEY], $_SESSION[self::SESSION_EMAIL_KEY]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function customer(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $customerId = (int) ($_SESSION[self::SESSION_ID_KEY] ?? 0);

        if ($customerId <= 0) {
            $this->clearSessionState();

            return null;
        }

        $customer = $this->customerService->findById($customerId);

        if ($customer === null || ($customer['is_active'] ?? false) !== true) {
            $this->clearSessionState();

            return null;
        }

        return $customer;
    }

    public function requireAuth(): void
    {
        if ($this->customer() !== null) {
            return;
        }

        header('Location: /account/login');
        exit;
    }

    public function logout(): void
    {
        $this->clearSessionState();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function syncSessionCustomer(array $customer): void
    {
        if (($customer['id'] ?? 0) <= 0) {
            return;
        }

        $_SESSION[self::SESSION_ID_KEY] = (int) ($customer['id'] ?? 0);
        $_SESSION[self::SESSION_EMAIL_KEY] = (string) ($customer['email'] ?? '');
        $_SESSION[self::SESSION_NAME_KEY] = (string) ($customer['full_name'] ?? '');
        $_SESSION[self::SESSION_LOGGED_IN_KEY] = true;
    }

    private function loginCustomer(array $customer): void
    {
        session_regenerate_id(true);

        $_SESSION[self::SESSION_ID_KEY] = (int) ($customer['id'] ?? 0);
        $_SESSION[self::SESSION_EMAIL_KEY] = (string) ($customer['email'] ?? '');
        $_SESSION[self::SESSION_NAME_KEY] = (string) ($customer['full_name'] ?? '');
        $_SESSION[self::SESSION_LOGGED_IN_KEY] = true;
    }

    private function clearSessionState(): void
    {
        unset(
            $_SESSION[self::SESSION_ID_KEY],
            $_SESSION[self::SESSION_EMAIL_KEY],
            $_SESSION[self::SESSION_NAME_KEY],
            $_SESSION[self::SESSION_LOGGED_IN_KEY]
        );
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
