<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\CSRF;
use App\Services\CustomerService;

final class ProfileController extends BaseAccountController
{
    private CustomerService $customerService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->customerService = new CustomerService($app);
    }

    public function showProfile(): string
    {
        $this->requireCustomer();

        $customer = $this->authService->customer();
        $oldInput = $_SESSION['_account_profile_old'] ?? [];
        unset($_SESSION['_account_profile_old']);

        return $this->renderStorefront('account-profile', [
            'pageTitle' => 'Account Profile',
            'error' => $this->consumeFlash('profile_error'),
            'success' => $this->consumeFlash('profile_success'),
            'formData' => is_array($oldInput) && $oldInput !== [] ? $oldInput : (is_array($customer) ? $customer : []),
        ]);
    }

    public function updateProfile(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('profile_error', 'The form session expired. Please try again.');
            $this->redirect('/account/profile');
        }

        $_SESSION['_account_profile_old'] = [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
        ];

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $result = $this->customerService->updateProfile($customerId, $_POST);

        if (!$result['success']) {
            $this->flash('profile_error', (string) ($result['error'] ?? 'Unable to update your profile.'));
            $this->redirect('/account/profile');
        }

        unset($_SESSION['_account_profile_old']);

        if (is_array($result['customer'])) {
            $this->authService->syncSessionCustomer($result['customer']);
        }

        $this->flash('profile_success', 'Your account profile has been updated.');
        $this->redirect('/account/profile');
    }

    public function showPassword(): string
    {
        $this->requireCustomer();

        return $this->renderStorefront('account-password', [
            'pageTitle' => 'Change Password',
            'error' => $this->consumeFlash('password_error'),
            'success' => $this->consumeFlash('password_success'),
        ]);
    }

    public function updatePassword(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('password_error', 'The form session expired. Please try again.');
            $this->redirect('/account/password');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $result = $this->customerService->changePassword(
            $customerId,
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['new_password_confirmation'] ?? '')
        );

        if (!$result['success']) {
            $this->flash('password_error', (string) ($result['error'] ?? 'Unable to change your password.'));
            $this->redirect('/account/password');
        }

        $this->flash('password_success', 'Your password has been updated.');
        $this->redirect('/account/password');
    }
}
