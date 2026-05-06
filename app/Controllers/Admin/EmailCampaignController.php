<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\CustomerService;
use App\Services\NotificationService;

final class EmailCampaignController extends BaseAdminController
{
    private CustomerService $customerService;
    private NotificationService $notificationService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->customerService = new CustomerService($app);
        $this->notificationService = new NotificationService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $search = trim((string) ($_GET['search'] ?? ''));
        $filter = trim((string) ($_GET['filter'] ?? 'subscribed'));
        $recipients = $this->customerService->listCampaignRecipients($search, $filter);

        return $this->renderAdmin('admin-email-campaigns', [
            'pageTitle' => 'Email Campaigns',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'search' => $search,
            'filter' => $filter,
            'recipients' => $recipients,
            'campaignLogs' => $this->campaignLogs(),
        ]);
    }

    public function send(): string
    {
        $this->requireAdmin();
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/email-campaigns');
        }

        $subject = trim((string) ($_POST['subject'] ?? ''));
        $preheader = trim((string) ($_POST['preheader'] ?? ''));
        $body = trim((string) ($_POST['message_body'] ?? ''));
        $ctaLabel = trim((string) ($_POST['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($_POST['cta_url'] ?? ''));
        $selectedIds = array_values(array_unique(array_map('intval', (array) ($_POST['customer_ids'] ?? []))));
        $qaOverride = trim((string) ($_POST['qa_override_email'] ?? ''));
        $isTestOnly = (string) ($_POST['is_test_only'] ?? '') === '1';

        if ($subject === '' || $body === '') {
            $this->flash('error', 'Subject and message are required.');
            $this->redirect('/admin/email-campaigns');
        }

        if ($ctaUrl !== '' && filter_var($ctaUrl, FILTER_VALIDATE_URL) === false) {
            $this->flash('error', 'CTA URL must be a valid URL.');
            $this->redirect('/admin/email-campaigns');
        }

        $all = $this->customerService->listCampaignRecipients('', 'subscribed');
        $allowed = [];
        foreach ($all as $customer) {
            $id = (int) ($customer['id'] ?? 0);
            if ($id > 0 && in_array($id, $selectedIds, true)) {
                $allowed[] = $customer;
            }
        }

        if ($allowed === []) {
            $this->flash('error', 'No eligible subscribed recipients selected.');
            $this->redirect('/admin/email-campaigns');
        }

        $sent = 0;
        $failed = 0;
        $targets = [];

        if ($qaOverride !== '' && filter_var($qaOverride, FILTER_VALIDATE_EMAIL) !== false) {
            $targets = [$qaOverride];
        } else {
            foreach ($allowed as $customer) {
                $email = trim((string) ($customer['email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                    $targets[] = $email;
                }
            }
        }

        if ($isTestOnly) {
            $targets = array_slice($targets, 0, 1);
        }

        foreach ($targets as $email) {
            $result = $this->notificationService->sendCampaignEmail($email, $subject, $preheader, $body, $ctaLabel, $ctaUrl);
            if (str_starts_with($result, 'sent_')) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->appendCampaignLog([
            'timestamp' => date('c'),
            'subject' => $subject,
            'selected_count' => count($selectedIds),
            'actual_target_count' => count($targets),
            'sent' => $sent,
            'failed' => $failed,
            'test_only' => $isTestOnly ? 1 : 0,
            'qa_override' => $qaOverride !== '' ? 1 : 0,
        ]);

        $this->flash('success', 'Campaign processed. Sent: ' . $sent . ' Failed: ' . $failed . '.');
        $this->redirect('/admin/email-campaigns');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function campaignLogs(): array
    {
        $path = $this->app->getBasePath('storage/logs/email_campaigns.log');
        if (!is_file($path)) {
            return [];
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $rows = [];
        foreach (array_slice($lines, -30) as $line) {
            $item = json_decode((string) $line, true);
            if (is_array($item)) {
                $rows[] = $item;
            }
        }
        return array_reverse($rows);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function appendCampaignLog(array $payload): void
    {
        $path = $this->app->getBasePath('storage/logs/email_campaigns.log');
        @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
}

