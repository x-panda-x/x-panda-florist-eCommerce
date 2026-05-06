<?php

declare(strict_types=1);

namespace App\Controllers\Account;

final class DashboardController extends BaseAccountController
{
    public function index(): string
    {
        $this->requireCustomer();

        return $this->renderStorefront('account-dashboard', [
            'pageTitle' => 'My Account',
            'success' => $this->consumeFlash('dashboard_success'),
        ]);
    }
}
