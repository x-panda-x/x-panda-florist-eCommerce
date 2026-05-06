<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\CSRF;
use App\Services\CustomerService;

final class PreferenceController extends BaseAccountController
{
    private CustomerService $customerService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->customerService = new CustomerService($app);
    }

    public function showEmailPreferences(): string
    {
        $this->requireCustomer();

        return $this->renderStorefront('account-email-preferences', [
            'pageTitle' => 'Email Preferences',
            'error' => $this->consumeFlash('preferences_error'),
            'success' => $this->consumeFlash('preferences_success'),
        ]);
    }

    public function updateEmailPreferences(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('preferences_error', 'The form session expired. Please try again.');
            $this->redirect('/account/email-preferences');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $result = $this->customerService->updateEmailPreferences($customerId, $_POST);

        if (!$result['success']) {
            $this->flash('preferences_error', (string) ($result['error'] ?? 'Unable to update your email preferences.'));
            $this->redirect('/account/email-preferences');
        }

        if (is_array($result['customer'])) {
            $this->authService->syncSessionCustomer($result['customer']);
        }

        $this->flash('preferences_success', 'Your email preferences have been updated.');
        $this->redirect('/account/email-preferences');
    }
}
