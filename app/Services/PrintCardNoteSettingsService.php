<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class PrintCardNoteSettingsService
{
    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'print_card_brand_display_name' => 'Lily and Rose',
            'print_card_brand_subtitle' => '',
            'print_card_front_kicker' => 'Gift Message',
            'print_card_center_heading' => 'A note for you',
            'print_card_empty_message_fallback' => 'No card message was provided for this order.',
            'print_card_details_heading' => 'Delivery Details',
            'print_card_label_product' => 'Flowers',
            'print_card_label_size' => 'Size',
            'print_card_label_recipient' => 'Recipient',
            'print_card_label_delivery_date' => 'Delivery Date',
            'print_card_label_store_contact' => 'Store Contact',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    public static function friendlyLabel(string $key): string
    {
        return match ($key) {
            'print_card_brand_display_name' => 'Brand display name',
            'print_card_brand_subtitle' => 'Small subtitle',
            'print_card_front_kicker' => 'Front panel label',
            'print_card_center_heading' => 'Center panel heading',
            'print_card_empty_message_fallback' => 'Empty-message fallback',
            'print_card_details_heading' => 'Right panel title',
            'print_card_label_product' => 'Product label',
            'print_card_label_size' => 'Size label',
            'print_card_label_recipient' => 'Recipient label',
            'print_card_label_delivery_date' => 'Delivery date label',
            'print_card_label_store_contact' => 'Store contact label',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }

    private Application $app;
    private SettingsService $settingsService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->settingsService = new SettingsService($app);
    }

    /**
     * @return array<string, string>
     */
    public function effectiveTexts(): array
    {
        $texts = [];

        foreach (self::defaults() as $key => $default) {
            $value = trim((string) $this->settingsService->get($key, ''));
            $texts[$key] = $value !== '' ? $value : $default;
        }

        return $texts;
    }

    /**
     * @return array<string, string>
     */
    public function storedTexts(): array
    {
        $texts = [];

        foreach (self::defaults() as $key => $default) {
            $texts[$key] = trim((string) $this->settingsService->get($key, ''));
        }

        return $texts;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizeInput(array $input): array
    {
        $settings = [];

        foreach (self::defaults() as $key => $default) {
            $value = trim((string) ($input[$key] ?? ''));
            $settings[$key] = $value === $default ? '' : $value;
        }

        return $settings;
    }

    /**
     * @param array<string, string> $settings
     */
    public function saveTexts(array $settings): void
    {
        foreach (self::keys() as $key) {
            $this->app->database()->execute(
                'INSERT INTO settings (setting_key, setting_value, autoload)
                 VALUES (:key, :value, 0)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [
                    'key' => $key,
                    'value' => $settings[$key] ?? '',
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, string>
     */
    public function previewTexts(array $overrides): array
    {
        $texts = self::defaults();

        foreach (self::keys() as $key) {
            if (!array_key_exists($key, $overrides)) {
                continue;
            }

            $value = trim((string) $overrides[$key]);
            $texts[$key] = $value !== '' ? $value : (self::defaults()[$key] ?? '');
        }

        return $texts;
    }
}
