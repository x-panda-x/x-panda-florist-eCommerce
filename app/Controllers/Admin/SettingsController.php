<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;

final class SettingsController extends BaseAdminController
{
    /**
     * @var array<int, string>
     */
    private array $settingKeys = [
        'store_name',
        'store_email',
        'store_phone',
        'email_delivery_mode',
        'same_day_cutoff',
        'sales_tax_rate',
    ];

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-settings', [
            'pageTitle' => 'Store Settings',
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
            $this->redirect('/admin/settings');
        }

        $settings = [
            'store_name' => trim((string) ($_POST['store_name'] ?? '')),
            'store_email' => trim((string) ($_POST['store_email'] ?? '')),
            'store_phone' => trim((string) ($_POST['store_phone'] ?? '')),
            'email_delivery_mode' => trim((string) ($_POST['email_delivery_mode'] ?? 'log_only')),
            'same_day_cutoff' => trim((string) ($_POST['same_day_cutoff'] ?? '')),
            'sales_tax_rate' => trim((string) ($_POST['sales_tax_rate'] ?? '')),
        ];

        $validationError = $this->validateSettings($settings);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/settings');
        }

        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }

        $this->flash('success', 'Store settings updated.');
        $this->redirect('/admin/settings');
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
        if ($settings['store_email'] !== '' && filter_var($settings['store_email'], FILTER_VALIDATE_EMAIL) === false) {
            return 'Enter a valid store email address.';
        }

        if (!in_array($settings['email_delivery_mode'], ['log_only', 'php_mail', 'smtp'], true)) {
            return 'Choose a valid email delivery mode.';
        }

        if ($settings['same_day_cutoff'] !== '' && preg_match('/^\d{2}:\d{2}$/', $settings['same_day_cutoff']) !== 1) {
            return 'Enter the same-day cutoff in HH:MM format.';
        }

        if ($settings['sales_tax_rate'] !== '') {
            if (!is_numeric($settings['sales_tax_rate'])) {
                return 'Enter the sales tax rate as a decimal value.';
            }

            $rate = (float) $settings['sales_tax_rate'];

            if ($rate < 0 || $rate > 1) {
                return 'Sales tax rate must be between 0 and 1.';
            }
        }

        return null;
    }
}
