<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\CustomerAddressService;
use App\Services\CustomerReminderService;
use App\Services\CustomerService;
use App\Services\OrderService;

final class CustomerController extends BaseAdminController
{
    private CustomerService $customerService;
    private CustomerAddressService $addressService;
    private CustomerReminderService $reminderService;
    private OrderService $orderService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->customerService = new CustomerService($app);
        $this->addressService = new CustomerAddressService($app);
        $this->reminderService = new CustomerReminderService($app);
        $this->orderService = new OrderService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();

        $search = trim((string) ($_GET['q'] ?? ''));

        return $this->renderAdmin('admin-customers', [
            'pageTitle' => 'Customers',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'searchQuery' => $search,
            'customers' => $this->customerService->listForAdmin($search),
        ]);
    }

    public function show(): string
    {
        $this->requireAdmin();

        $customerId = (int) ($_GET['id'] ?? 0);
        $customer = $this->customerService->findAdminProfileById($customerId);

        if ($customer === null) {
            $this->flash('error', 'Customer not found.');
            $this->redirect('/admin/customers');
        }

        return $this->renderAdmin('admin-customer-view', [
            'pageTitle' => 'Customer ' . (string) ($customer['full_name'] ?? ''),
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'customer' => $customer,
            'orders' => $this->orderService->listOrdersForCustomer($customer),
            'addresses' => $this->addressService->listByCustomerId((int) ($customer['id'] ?? 0)),
            'reminders' => $this->reminderService->listByCustomerId((int) ($customer['id'] ?? 0)),
        ]);
    }

    public function toggleStatus(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/customers');
        }

        $customerId = (int) ($_POST['id'] ?? 0);
        $returnTo = trim((string) ($_POST['return_to'] ?? '/admin/customers'));
        $customer = $this->customerService->findById($customerId);

        if ($customer === null) {
            $this->flash('error', 'Customer not found.');
            $this->redirect('/admin/customers');
        }

        try {
            $this->customerService->setActiveStatus($customerId, !((bool) ($customer['is_active'] ?? false)));
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update customer account status.');
            $this->redirect($this->safeReturnPath($returnTo, $customerId));
        }

        $this->flash('success', !empty($customer['is_active']) ? 'Customer account disabled.' : 'Customer account reactivated.');
        $this->redirect($this->safeReturnPath($returnTo, $customerId));
    }

    private function safeReturnPath(string $path, int $customerId): string
    {
        if ($path === '/admin/customers/view') {
            return '/admin/customers/view?id=' . $customerId;
        }

        return '/admin/customers';
    }
}
