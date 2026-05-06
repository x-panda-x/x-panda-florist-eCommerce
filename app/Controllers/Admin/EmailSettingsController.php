<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;

final class EmailSettingsController extends BaseAdminController
{
    /**
     * @var array<int, string>
     */
    private array $settingKeys = [
        'store_name',
        'store_email',
        'store_phone',
        'store_address',
        'public_base_url',
        'email_sender_name',
        'email_reply_to',
        'email_footer_text',
        'email_support_message',
        'instagram_url',
        'facebook_url',
        'x_url',
        'tiktok_url',
    ];

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-email-settings', [
            'pageTitle' => 'Email Settings',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'settings' => $this->loadSettings(),
        ]);
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/email-settings');
        }

        $settings = [
            'store_name' => trim((string) ($_POST['store_name'] ?? '')),
            'store_email' => trim((string) ($_POST['store_email'] ?? '')),
            'store_phone' => trim((string) ($_POST['store_phone'] ?? '')),
            'store_address' => trim((string) ($_POST['store_address'] ?? '')),
            'public_base_url' => trim((string) ($_POST['public_base_url'] ?? '')),
            'email_sender_name' => trim((string) ($_POST['email_sender_name'] ?? '')),
            'email_reply_to' => trim((string) ($_POST['email_reply_to'] ?? '')),
            'email_footer_text' => trim((string) ($_POST['email_footer_text'] ?? '')),
            'email_support_message' => trim((string) ($_POST['email_support_message'] ?? '')),
            'instagram_url' => trim((string) ($_POST['instagram_url'] ?? '')),
            'facebook_url' => trim((string) ($_POST['facebook_url'] ?? '')),
            'x_url' => trim((string) ($_POST['x_url'] ?? '')),
            'tiktok_url' => trim((string) ($_POST['tiktok_url'] ?? '')),
        ];

        $validationError = $this->validateSettings($settings);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/email-settings');
        }

        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }

        $this->flash('success', 'Email settings updated.');
        $this->redirect('/admin/email-settings');
    }

    /**
     * @return array<string, string>
     */
    private function loadSettings(): array
    {
        $placeholders = implode(', ', array_fill(0, count($this->settingKeys), '?'));
        $rows = $this->app->database()->fetchAll(
            'SELECT setting_key, setting_value FROM settings WHERE setting_key IN (' . $placeholders . ')',
            $this->settingKeys
        );

        $settings = array_fill_keys($this->settingKeys, '');

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');

            if (array_key_exists($key, $settings)) {
                $settings[$key] = (string) ($row['setting_value'] ?? '');
            }
        }

        return $settings;
    }

    private function saveSetting(string $key, string $value): void
    {
        $this->app->database()->execute(
            'INSERT INTO settings (setting_key, setting_value, autoload)
             VALUES (:key, :value, 0)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [
                'key' => $key,
                'value' => $value,
            ]
        );
    }

    /**
     * @param array<string, string> $settings
     */
    private function validateSettings(array $settings): ?string
    {
        foreach (['store_email', 'email_reply_to'] as $emailKey) {
            if ($settings[$emailKey] !== '' && filter_var($settings[$emailKey], FILTER_VALIDATE_EMAIL) === false) {
                return 'Enter a valid email for ' . str_replace('_', ' ', $emailKey) . '.';
            }
        }

        if ($settings['public_base_url'] !== '' && filter_var($settings['public_base_url'], FILTER_VALIDATE_URL) === false) {
            return 'Enter a valid website URL.';
        }

        foreach (['instagram_url', 'facebook_url', 'x_url', 'tiktok_url'] as $urlKey) {
            if ($settings[$urlKey] !== '' && filter_var($settings[$urlKey], FILTER_VALIDATE_URL) === false) {
                return 'Enter a valid URL for ' . str_replace('_', ' ', $urlKey) . '.';
            }
        }

        return null;
    }
}

