<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\PrintCardNoteSettingsService;
use App\Services\SettingsService;

final class SiteSettingsController extends BaseAdminController
{
    private PrintCardNoteSettingsService $printCardNoteSettingsService;
    private SettingsService $settingsService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->printCardNoteSettingsService = new PrintCardNoteSettingsService($app);
        $this->settingsService = new SettingsService($app);
    }

    /**
     * @var array<int, string>
     */
    private array $settingKeys = [
        'store_name',
        'store_phone',
        'store_email',
        'public_base_url',
        'email_delivery_mode',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'store_address',
        'support_text',
        'business_info',
        'instagram_url',
        'facebook_url',
        'x_url',
        'tiktok_url',
    ];

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-site-settings', [
            'pageTitle' => 'Site Settings',
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
            $this->redirect('/admin/site-settings');
        }

        $existingSettings = $this->loadSettings();

        $settings = [
            'store_name' => trim((string) ($_POST['store_name'] ?? '')),
            'store_phone' => trim((string) ($_POST['store_phone'] ?? '')),
            'store_email' => trim((string) ($_POST['store_email'] ?? '')),
            'public_base_url' => trim((string) ($_POST['public_base_url'] ?? '')),
            'email_delivery_mode' => trim((string) ($_POST['email_delivery_mode'] ?? 'log_only')),
            'smtp_host' => trim((string) ($_POST['smtp_host'] ?? 'smtp.gmail.com')),
            'smtp_port' => trim((string) ($_POST['smtp_port'] ?? '587')),
            'smtp_encryption' => trim((string) ($_POST['smtp_encryption'] ?? 'tls')),
            'smtp_username' => trim((string) ($_POST['smtp_username'] ?? '')),
            'smtp_password' => trim((string) ($_POST['smtp_password'] ?? '')),
            'store_address' => trim((string) ($_POST['store_address'] ?? '')),
            'support_text' => trim((string) ($_POST['support_text'] ?? '')),
            'business_info' => trim((string) ($_POST['business_info'] ?? '')),
            'instagram_url' => trim((string) ($_POST['instagram_url'] ?? '')),
            'facebook_url' => trim((string) ($_POST['facebook_url'] ?? '')),
            'x_url' => trim((string) ($_POST['x_url'] ?? '')),
            'tiktok_url' => trim((string) ($_POST['tiktok_url'] ?? '')),
        ];

        if ($settings['smtp_password'] === '') {
            $settings['smtp_password'] = (string) ($existingSettings['smtp_password'] ?? '');
        }

        $validationError = $this->validateSettings($settings);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/site-settings');
        }

        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }

        $this->flash('success', 'Site settings updated.');
        $this->redirect('/admin/site-settings');
    }

    public function cardNote(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-card-note-settings', [
            'pageTitle' => 'Print Card Note Settings',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'defaults' => PrintCardNoteSettingsService::defaults(),
            'storedTexts' => $this->printCardNoteSettingsService->storedTexts(),
            'effectiveTexts' => $this->printCardNoteSettingsService->effectiveTexts(),
        ]);
    }

    public function updateCardNote(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/site-settings/card-note');
        }

        if (isset($_POST['reset_all'])) {
            $settings = array_fill_keys(PrintCardNoteSettingsService::keys(), '');
        } else {
            $settings = $this->printCardNoteSettingsService->normalizeInput($_POST);

            $resetKeys = is_array($_POST['reset_field'] ?? null) ? $_POST['reset_field'] : [];

            foreach ($resetKeys as $key => $enabled) {
                if ((string) $enabled !== '1' || !in_array((string) $key, PrintCardNoteSettingsService::keys(), true)) {
                    continue;
                }

                $settings[(string) $key] = '';
            }
        }

        $this->printCardNoteSettingsService->saveTexts($settings);

        $this->flash('success', isset($_POST['reset_all']) ? 'Print card note text reset to defaults.' : 'Print card note text updated.');
        $this->redirect('/admin/site-settings/card-note');
    }

    public function cardNotePreview(): string
    {
        $this->requireAdmin();

        $overrides = is_array($_GET['preview'] ?? null) ? $_GET['preview'] : [];

        return $this->view('admin-order-card-note-print', [
            'pageTitle' => 'Print Card Note Preview',
            'order' => [
                'id' => 0,
                'order_number' => 'PREVIEW',
                'card_message' => 'With love and warm wishes, this arrangement was chosen just for you.',
                'recipient_name' => 'Sample Recipient',
                'delivery_date' => date('Y-m-d', strtotime('+3 days')),
            ],
            'items' => [[
                'product_name' => 'Garden Rose Bouquet',
                'variant_name' => 'Deluxe',
            ]],
            'store' => [
                'name' => trim((string) $this->settingsService->get('store_name', 'Lily and Rose')),
                'address' => trim((string) $this->settingsService->get('store_address', '')),
                'phone' => trim((string) $this->settingsService->get('store_phone', '')),
                'email' => trim((string) $this->settingsService->get('store_email', '')),
            ],
            'cardText' => $this->printCardNoteSettingsService->previewTexts($overrides),
            'autoPrint' => false,
            'hideToolbar' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function loadSettings(): array
    {
        $placeholders = implode(', ', array_fill(0, count($this->settingKeys), '?'));
        $rows = $this->app->database()->fetchAll(
            'SELECT setting_key, setting_value
             FROM settings
             WHERE setting_key IN (' . $placeholders . ')',
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

        if ($settings['public_base_url'] !== '' && filter_var($settings['public_base_url'], FILTER_VALIDATE_URL) === false) {
            return 'Enter a valid public base URL.';
        }

        if (!in_array($settings['email_delivery_mode'], ['log_only', 'php_mail', 'smtp'], true)) {
            return 'Choose a valid email delivery mode.';
        }

        if ($settings['smtp_port'] !== '' && (!ctype_digit($settings['smtp_port']) || (int) $settings['smtp_port'] <= 0)) {
            return 'Enter a valid SMTP port.';
        }

        if (!in_array($settings['smtp_encryption'], ['tls', 'ssl', 'none'], true)) {
            return 'Choose a valid SMTP encryption setting.';
        }

        if ($settings['smtp_username'] !== '' && filter_var($settings['smtp_username'], FILTER_VALIDATE_EMAIL) === false) {
            return 'Enter a valid SMTP username email address.';
        }

        foreach (['instagram_url', 'facebook_url', 'x_url', 'tiktok_url'] as $urlKey) {
            $value = $settings[$urlKey] ?? '';

            if ($value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                return 'Enter a valid URL for ' . str_replace('_', ' ', $urlKey) . '.';
            }
        }

        return null;
    }
}
