<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\CSRF;
use App\Services\CustomerAddressService;

final class AddressController extends BaseAccountController
{
    private CustomerAddressService $addressService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->addressService = new CustomerAddressService($app);
    }

    public function index(): string
    {
        $this->requireCustomer();

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $createOld = $_SESSION['_account_address_create_old'] ?? [];
        unset($_SESSION['_account_address_create_old']);

        return $this->renderStorefront('account-address-book', [
            'pageTitle' => 'Address Book',
            'error' => $this->consumeFlash('address_error'),
            'success' => $this->consumeFlash('address_success'),
            'createFormData' => is_array($createOld) ? $createOld : [],
            'addresses' => $this->addressService->listByCustomerId($customerId),
        ]);
    }

    public function create(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('address_error', 'The form session expired. Please try again.');
            $this->redirect('/account/addresses');
        }

        $_SESSION['_account_address_create_old'] = [
            'label' => trim((string) ($_POST['label'] ?? '')),
            'recipient_name' => trim((string) ($_POST['recipient_name'] ?? '')),
            'delivery_address' => trim((string) ($_POST['delivery_address'] ?? '')),
            'delivery_zip' => trim((string) ($_POST['delivery_zip'] ?? '')),
            'delivery_instructions' => trim((string) ($_POST['delivery_instructions'] ?? '')),
            'is_default' => ($_POST['is_default'] ?? null) === '1' ? '1' : '',
        ];

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $result = $this->addressService->createForCustomer($customerId, $_POST);

        if (!$result['success']) {
            $this->flash('address_error', (string) ($result['error'] ?? 'Unable to save the address.'));
            $this->redirect('/account/addresses');
        }

        unset($_SESSION['_account_address_create_old']);
        $this->flash('address_success', 'Address saved.');
        $this->redirect('/account/addresses');
    }

    public function update(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('address_error', 'The form session expired. Please try again.');
            $this->redirect('/account/addresses');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $addressId = (int) ($_POST['address_id'] ?? 0);
        $result = $this->addressService->updateForCustomer($customerId, $addressId, $_POST);

        if (!$result['success']) {
            $this->flash('address_error', (string) ($result['error'] ?? 'Unable to update the address.'));
            $this->redirect('/account/addresses');
        }

        $this->flash('address_success', 'Address updated.');
        $this->redirect('/account/addresses');
    }

    public function delete(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('address_error', 'The form session expired. Please try again.');
            $this->redirect('/account/addresses');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $addressId = (int) ($_POST['address_id'] ?? 0);
        $result = $this->addressService->deleteForCustomer($customerId, $addressId);

        if (!$result['success']) {
            $this->flash('address_error', (string) ($result['error'] ?? 'Unable to delete the address.'));
            $this->redirect('/account/addresses');
        }

        $this->flash('address_success', 'Address deleted.');
        $this->redirect('/account/addresses');
    }

    public function setDefault(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('address_error', 'The form session expired. Please try again.');
            $this->redirect('/account/addresses');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $addressId = (int) ($_POST['address_id'] ?? 0);
        $result = $this->addressService->setDefaultForCustomer($customerId, $addressId);

        if (!$result['success']) {
            $this->flash('address_error', (string) ($result['error'] ?? 'Unable to set the default address.'));
            $this->redirect('/account/addresses');
        }

        $this->flash('address_success', 'Default address updated.');
        $this->redirect('/account/addresses');
    }
}
