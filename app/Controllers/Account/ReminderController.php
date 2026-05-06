<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\CSRF;
use App\Services\CustomerReminderService;
use App\Services\ProductService;

final class ReminderController extends BaseAccountController
{
    private CustomerReminderService $reminderService;
    private ProductService $productService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->reminderService = new CustomerReminderService($app);
        $this->productService = new ProductService($app);
    }

    public function index(): string
    {
        $this->requireCustomer();

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $createOld = $_SESSION['_account_reminder_create_old'] ?? [];
        $draftReminder = $_SESSION['reminder_draft'] ?? [];
        unset($_SESSION['_account_reminder_create_old']);

        if (is_array($draftReminder) && (int) ($draftReminder['customer_id'] ?? 0) !== $customerId) {
            unset($_SESSION['reminder_draft']);
            $draftReminder = [];
        }

        $createFormData = is_array($createOld) && $createOld !== []
            ? $createOld
            : (is_array($draftReminder) ? $draftReminder : []);

        return $this->renderStorefront('account-reminders', [
            'pageTitle' => 'Reminders',
            'error' => $this->consumeFlash('reminder_error'),
            'success' => $this->consumeFlash('reminder_success'),
            'createFormData' => $createFormData,
            'draftReminder' => is_array($draftReminder) ? $draftReminder : [],
            'reminders' => $this->reminderService->listByCustomerId($customerId),
        ]);
    }

    public function create(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('reminder_error', 'The form session expired. Please try again.');
            $this->redirect('/account/reminders');
        }

        $_SESSION['_account_reminder_create_old'] = [
            'occasion_label' => trim((string) ($_POST['occasion_label'] ?? '')),
            'recipient_name' => trim((string) ($_POST['recipient_name'] ?? '')),
            'reminder_date' => trim((string) ($_POST['reminder_date'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
            'is_active' => ($_POST['is_active'] ?? null) === '1' ? '1' : '',
            'create_action' => trim((string) ($_POST['create_action'] ?? 'save')),
        ];

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $input = $this->reminderService->normalizeInput($_POST);
        $validationError = $this->reminderService->validateInput($input);

        if ($validationError !== null) {
            $this->flash('reminder_error', $validationError);
            $this->redirect('/account/reminders');
        }

        $result = $this->reminderService->createForCustomer($customerId, $_POST);

        if (!$result['success'] || !is_array($result['reminder'] ?? null)) {
            $this->flash('reminder_error', (string) ($result['error'] ?? 'Unable to create the reminder.'));
            $this->redirect('/account/reminders');
        }

        unset($_SESSION['_account_reminder_create_old']);
        $reminder = $result['reminder'];
        $createAction = trim((string) ($_POST['create_action'] ?? 'save'));

        if ($createAction === 'shop') {
            $_SESSION['reminder_draft'] = [
                'reminder_id' => (int) ($reminder['id'] ?? 0),
                'customer_id' => $customerId,
                'occasion_label' => $input['occasion_label'],
                'recipient_name' => $input['recipient_name'],
                'reminder_date' => $input['reminder_date'],
                'note' => $input['note'],
                'is_active' => ($_POST['is_active'] ?? null) === '1' ? '1' : '',
                'prepared_at' => date('c'),
                'product_id' => (int) ($reminder['product_id'] ?? 0),
                'product_slug' => (string) ($reminder['product_slug'] ?? ''),
                'variant_id' => 0,
                'order_id' => 0,
                'order_number' => '',
                'access_token' => '',
            ];

            $this->flash('reminder_success', 'Reminder saved. Continue shopping to attach a paid order before the date arrives.');
            $this->redirect($this->buildReminderBrowsePath($input['occasion_label']));
        }

        unset($_SESSION['reminder_draft']);
        $this->flash('reminder_success', 'Reminder saved. You can come back later to complete the purchase or attach an order.');
        $this->redirect('/account/reminders');
    }

    public function update(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('reminder_error', 'The form session expired. Please try again.');
            $this->redirect('/account/reminders');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $reminderId = (int) ($_POST['reminder_id'] ?? 0);
        $result = $this->reminderService->updateForCustomer($customerId, $reminderId, $_POST);

        if (!$result['success']) {
            $this->flash('reminder_error', (string) ($result['error'] ?? 'Unable to update the reminder.'));
            $this->redirect('/account/reminders');
        }

        $this->flash('reminder_success', 'Reminder updated.');
        $this->redirect('/account/reminders');
    }

    public function delete(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('reminder_error', 'The form session expired. Please try again.');
            $this->redirect('/account/reminders');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $reminderId = (int) ($_POST['reminder_id'] ?? 0);
        $result = $this->reminderService->deleteForCustomer($customerId, $reminderId);

        if (!$result['success']) {
            $this->flash('reminder_error', (string) ($result['error'] ?? 'Unable to delete the reminder.'));
            $this->redirect('/account/reminders');
        }

        $this->flash('reminder_success', 'Reminder deleted.');
        $this->redirect('/account/reminders');
    }

    public function toggle(): string
    {
        $this->requireCustomer();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('reminder_error', 'The form session expired. Please try again.');
            $this->redirect('/account/reminders');
        }

        $customer = $this->authService->customer();
        $customerId = (int) ($customer['id'] ?? 0);
        $reminderId = (int) ($_POST['reminder_id'] ?? 0);
        $result = $this->reminderService->toggleForCustomer($customerId, $reminderId);

        if (!$result['success']) {
            $this->flash('reminder_error', (string) ($result['error'] ?? 'Unable to update the reminder status.'));
            $this->redirect('/account/reminders');
        }

        $this->flash('reminder_success', 'Reminder status updated.');
        $this->redirect('/account/reminders');
    }

    private function buildReminderBrowsePath(string $occasionLabel): string
    {
        $normalized = $this->normalizeOccasionLookup($occasionLabel);

        if ($normalized === '') {
            return '/best-sellers';
        }

        foreach ($this->productService->listOccasions() as $occasion) {
            if (!is_array($occasion)) {
                continue;
            }

            $occasionSlug = trim((string) ($occasion['slug'] ?? ''));
            $occasionName = trim((string) ($occasion['name'] ?? ''));

            if ($occasionSlug !== '' && $normalized === $this->normalizeOccasionLookup($occasionSlug)) {
                return '/occasions?' . http_build_query(['occasion' => $occasionSlug]);
            }

            if ($occasionName !== '' && $normalized === $this->normalizeOccasionLookup($occasionName)) {
                return '/occasions?' . http_build_query(['occasion' => $occasionSlug !== '' ? $occasionSlug : $normalized]);
            }
        }

        return '/best-sellers';
    }

    private function normalizeOccasionLookup(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim($normalized, '-');
    }
}
