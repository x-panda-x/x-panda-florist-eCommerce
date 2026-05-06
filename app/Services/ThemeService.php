<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class ThemeService
{
    private const DEFAULT_SCOPE = 'storefront';
    private const DEFAULT_PRESET_KEY = 'storefront-default';
    /**
     * @var array<int, string>
     */
    private const STOREFRONT_TOKEN_KEYS = [
        'bg_color',
        'bg_accent_color',
        'surface_color',
        'surface_strong_color',
        'surface_soft_color',
        'line_color',
        'line_strong_color',
        'text_color',
        'muted_text_color',
        'accent_color',
        'accent_deep_color',
        'accent_soft_color',
        'promo_strip_bg_color',
        'promo_strip_text_color',
        'button_primary_bg',
        'button_primary_text',
        'button_secondary_bg',
        'button_secondary_text',
        'footer_bg_color',
        'footer_text_color',
    ];

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPresets(): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT *
             FROM theme_presets
             ORDER BY is_default DESC, is_system DESC, name ASC, id ASC'
        );

        return array_map(fn (array $row): array => $this->hydratePreset($row), $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPresetById(int $presetId): ?array
    {
        if ($presetId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT *
             FROM theme_presets
             WHERE id = :id
             LIMIT 1',
            ['id' => $presetId]
        )->fetch();

        return is_array($row) ? $this->hydratePreset($row) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getThemeSettings(string $scope = self::DEFAULT_SCOPE): array
    {
        $scope = $this->normalizeScope($scope);
        $this->ensureThemeFoundation($scope);

        $row = $this->app->database()->query(
            'SELECT *
             FROM theme_settings
             WHERE scope = :scope
             LIMIT 1',
            ['scope' => $scope]
        )->fetch();

        if (!is_array($row)) {
            return $this->defaultSettings($scope);
        }

        return $this->hydrateSettings($row);
    }

    public function saveThemeSettings(string $scope, array $input): void
    {
        $scope = $this->normalizeScope($scope);
        $this->ensureThemeFoundation($scope);

        $settings = $this->defaultSettings($scope);
        $presetId = isset($input['active_preset_id']) && (int) $input['active_preset_id'] > 0
            ? (int) $input['active_preset_id']
            : null;

        $this->app->database()->execute(
            'INSERT INTO theme_settings (
                scope,
                active_preset_id,
                bg_color,
                bg_accent_color,
                surface_color,
                surface_strong_color,
                surface_soft_color,
                line_color,
                line_strong_color,
                text_color,
                muted_text_color,
                accent_color,
                accent_deep_color,
                accent_soft_color,
                promo_strip_bg_color,
                promo_strip_text_color,
                button_primary_bg,
                button_primary_text,
                button_secondary_bg,
                button_secondary_text,
                footer_bg_color,
                footer_text_color
             ) VALUES (
                :scope,
                :active_preset_id,
                :bg_color,
                :bg_accent_color,
                :surface_color,
                :surface_strong_color,
                :surface_soft_color,
                :line_color,
                :line_strong_color,
                :text_color,
                :muted_text_color,
                :accent_color,
                :accent_deep_color,
                :accent_soft_color,
                :promo_strip_bg_color,
                :promo_strip_text_color,
                :button_primary_bg,
                :button_primary_text,
                :button_secondary_bg,
                :button_secondary_text,
                :footer_bg_color,
                :footer_text_color
             )
             ON DUPLICATE KEY UPDATE
                active_preset_id = VALUES(active_preset_id),
                bg_color = VALUES(bg_color),
                bg_accent_color = VALUES(bg_accent_color),
                surface_color = VALUES(surface_color),
                surface_strong_color = VALUES(surface_strong_color),
                surface_soft_color = VALUES(surface_soft_color),
                line_color = VALUES(line_color),
                line_strong_color = VALUES(line_strong_color),
                text_color = VALUES(text_color),
                muted_text_color = VALUES(muted_text_color),
                accent_color = VALUES(accent_color),
                accent_deep_color = VALUES(accent_deep_color),
                accent_soft_color = VALUES(accent_soft_color),
                promo_strip_bg_color = VALUES(promo_strip_bg_color),
                promo_strip_text_color = VALUES(promo_strip_text_color),
                button_primary_bg = VALUES(button_primary_bg),
                button_primary_text = VALUES(button_primary_text),
                button_secondary_bg = VALUES(button_secondary_bg),
                button_secondary_text = VALUES(button_secondary_text),
                footer_bg_color = VALUES(footer_bg_color),
                footer_text_color = VALUES(footer_text_color)',
            [
                'scope' => $scope,
                'active_preset_id' => $presetId,
                'bg_color' => (string) ($input['bg_color'] ?? $settings['bg_color']),
                'bg_accent_color' => (string) ($input['bg_accent_color'] ?? $settings['bg_accent_color']),
                'surface_color' => (string) ($input['surface_color'] ?? $settings['surface_color']),
                'surface_strong_color' => (string) ($input['surface_strong_color'] ?? $settings['surface_strong_color']),
                'surface_soft_color' => (string) ($input['surface_soft_color'] ?? $settings['surface_soft_color']),
                'line_color' => (string) ($input['line_color'] ?? $settings['line_color']),
                'line_strong_color' => (string) ($input['line_strong_color'] ?? $settings['line_strong_color']),
                'text_color' => (string) ($input['text_color'] ?? $settings['text_color']),
                'muted_text_color' => (string) ($input['muted_text_color'] ?? $settings['muted_text_color']),
                'accent_color' => (string) ($input['accent_color'] ?? $settings['accent_color']),
                'accent_deep_color' => (string) ($input['accent_deep_color'] ?? $settings['accent_deep_color']),
                'accent_soft_color' => (string) ($input['accent_soft_color'] ?? $settings['accent_soft_color']),
                'promo_strip_bg_color' => (string) ($input['promo_strip_bg_color'] ?? $settings['promo_strip_bg_color']),
                'promo_strip_text_color' => (string) ($input['promo_strip_text_color'] ?? $settings['promo_strip_text_color']),
                'button_primary_bg' => (string) ($input['button_primary_bg'] ?? $settings['button_primary_bg']),
                'button_primary_text' => (string) ($input['button_primary_text'] ?? $settings['button_primary_text']),
                'button_secondary_bg' => (string) ($input['button_secondary_bg'] ?? $settings['button_secondary_bg']),
                'button_secondary_text' => (string) ($input['button_secondary_text'] ?? $settings['button_secondary_text']),
                'footer_bg_color' => (string) ($input['footer_bg_color'] ?? $settings['footer_bg_color']),
                'footer_text_color' => (string) ($input['footer_text_color'] ?? $settings['footer_text_color']),
            ]
        );
    }

    public function ensureThemeFoundation(string $scope = self::DEFAULT_SCOPE): void
    {
        $scope = $this->normalizeScope($scope);
        $preset = $this->ensureDefaultPreset();
        $existing = $this->app->database()->query(
            'SELECT id
             FROM theme_settings
             WHERE scope = :scope
             LIMIT 1',
            ['scope' => $scope]
        )->fetch();

        if (is_array($existing)) {
            return;
        }

        $defaults = $this->defaultSettings($scope);
        $this->insertThemeSettingsRow(array_merge($defaults, [
            'active_preset_id' => $preset['id'] ?? null,
        ]));
    }

    /**
     * @return array<string, string>
     */
    public function defaultTokens(): array
    {
        return [
            'bg_color' => '#f7f1ea',
            'bg_accent_color' => '#efe0d1',
            'surface_color' => 'rgba(255, 252, 248, 0.94)',
            'surface_strong_color' => '#ffffff',
            'surface_soft_color' => '#f2e6dc',
            'line_color' => '#dfcdbf',
            'line_strong_color' => '#c7a994',
            'text_color' => '#2e221c',
            'muted_text_color' => '#705b50',
            'accent_color' => '#a2465b',
            'accent_deep_color' => '#7e3347',
            'accent_soft_color' => '#f6d8df',
            'promo_strip_bg_color' => '#2f5345',
            'promo_strip_text_color' => '#f8f2ed',
            'button_primary_bg' => '#a2465b',
            'button_primary_text' => '#fff8f7',
            'button_secondary_bg' => '#ffffff',
            'button_secondary_text' => '#2e221c',
            'footer_bg_color' => '#2f5345',
            'footer_text_color' => '#f7efe8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function storefrontCssVariables(): array
    {
        $settings = $this->getThemeSettings(self::DEFAULT_SCOPE);
        $defaults = $this->defaultTokens();

        return [
            '--bg' => $this->sanitizeCssColorValue((string) ($settings['bg_color'] ?? ''), $defaults['bg_color']),
            '--bg-accent' => $this->sanitizeCssColorValue((string) ($settings['bg_accent_color'] ?? ''), $defaults['bg_accent_color']),
            '--surface' => $this->sanitizeCssColorValue((string) ($settings['surface_color'] ?? ''), $defaults['surface_color']),
            '--surface-strong' => $this->sanitizeCssColorValue((string) ($settings['surface_strong_color'] ?? ''), $defaults['surface_strong_color']),
            '--surface-soft' => $this->sanitizeCssColorValue((string) ($settings['surface_soft_color'] ?? ''), $defaults['surface_soft_color']),
            '--line' => $this->sanitizeCssColorValue((string) ($settings['line_color'] ?? ''), $defaults['line_color']),
            '--line-strong' => $this->sanitizeCssColorValue((string) ($settings['line_strong_color'] ?? ''), $defaults['line_strong_color']),
            '--text' => $this->sanitizeCssColorValue((string) ($settings['text_color'] ?? ''), $defaults['text_color']),
            '--muted' => $this->sanitizeCssColorValue((string) ($settings['muted_text_color'] ?? ''), $defaults['muted_text_color']),
            '--accent' => $this->sanitizeCssColorValue((string) ($settings['accent_color'] ?? ''), $defaults['accent_color']),
            '--accent-deep' => $this->sanitizeCssColorValue((string) ($settings['accent_deep_color'] ?? ''), $defaults['accent_deep_color']),
            '--accent-soft' => $this->sanitizeCssColorValue((string) ($settings['accent_soft_color'] ?? ''), $defaults['accent_soft_color']),
            '--forest' => $this->sanitizeCssColorValue((string) ($settings['promo_strip_bg_color'] ?? ''), $defaults['promo_strip_bg_color']),
            '--promo-strip-text' => $this->sanitizeCssColorValue((string) ($settings['promo_strip_text_color'] ?? ''), $defaults['promo_strip_text_color']),
            '--button-primary-bg' => $this->sanitizeCssColorValue((string) ($settings['button_primary_bg'] ?? ''), $defaults['button_primary_bg']),
            '--button-primary-text' => $this->sanitizeCssColorValue((string) ($settings['button_primary_text'] ?? ''), $defaults['button_primary_text']),
            '--button-secondary-bg' => $this->sanitizeCssColorValue((string) ($settings['button_secondary_bg'] ?? ''), $defaults['button_secondary_bg']),
            '--button-secondary-text' => $this->sanitizeCssColorValue((string) ($settings['button_secondary_text'] ?? ''), $defaults['button_secondary_text']),
            '--footer-bg' => $this->sanitizeCssColorValue((string) ($settings['footer_bg_color'] ?? ''), $defaults['footer_bg_color']),
            '--footer-text' => $this->sanitizeCssColorValue((string) ($settings['footer_text_color'] ?? ''), $defaults['footer_text_color']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ensureDefaultPreset(): array
    {
        $row = $this->app->database()->query(
            'SELECT *
             FROM theme_presets
             WHERE preset_key = :preset_key
             LIMIT 1',
            ['preset_key' => self::DEFAULT_PRESET_KEY]
        )->fetch();

        if (is_array($row)) {
            return $this->hydratePreset($row);
        }

        $this->app->database()->execute(
            'INSERT INTO theme_presets (preset_key, name, tokens_json, is_system, is_default)
             VALUES (:preset_key, :name, :tokens_json, 1, 1)',
            [
                'preset_key' => self::DEFAULT_PRESET_KEY,
                'name' => 'Storefront Default',
                'tokens_json' => json_encode($this->defaultTokens(), JSON_THROW_ON_ERROR),
            ]
        );

        $presetId = (int) $this->app->database()->connection()->lastInsertId();

        $preset = $this->findPresetById($presetId);

        return is_array($preset) ? $preset : [
            'id' => $presetId,
            'preset_key' => self::DEFAULT_PRESET_KEY,
            'name' => 'Storefront Default',
            'tokens' => $this->defaultTokens(),
            'is_system' => true,
            'is_default' => true,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydratePreset(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_system'] = !empty($row['is_system']);
        $row['is_default'] = !empty($row['is_default']);
        $row['tokens'] = $this->decodeJsonObject($row['tokens_json'] ?? null) ?? $this->defaultTokens();

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateSettings(array $row): array
    {
        $defaults = $this->defaultSettings((string) ($row['scope'] ?? self::DEFAULT_SCOPE));

        foreach (self::STOREFRONT_TOKEN_KEYS as $key) {
            $defaults[$key] = (string) ($row[$key] ?? $defaults[$key]);
        }

        $defaults['id'] = (int) ($row['id'] ?? 0);
        $defaults['active_preset_id'] = isset($row['active_preset_id']) ? (int) $row['active_preset_id'] : null;

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(string $scope): array
    {
        return array_merge([
            'id' => 0,
            'scope' => $scope,
            'active_preset_id' => null,
        ], $this->defaultTokens());
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function insertThemeSettingsRow(array $settings): void
    {
        $this->app->database()->execute(
            'INSERT INTO theme_settings (
                scope,
                active_preset_id,
                bg_color,
                bg_accent_color,
                surface_color,
                surface_strong_color,
                surface_soft_color,
                line_color,
                line_strong_color,
                text_color,
                muted_text_color,
                accent_color,
                accent_deep_color,
                accent_soft_color,
                promo_strip_bg_color,
                promo_strip_text_color,
                button_primary_bg,
                button_primary_text,
                button_secondary_bg,
                button_secondary_text,
                footer_bg_color,
                footer_text_color
             ) VALUES (
                :scope,
                :active_preset_id,
                :bg_color,
                :bg_accent_color,
                :surface_color,
                :surface_strong_color,
                :surface_soft_color,
                :line_color,
                :line_strong_color,
                :text_color,
                :muted_text_color,
                :accent_color,
                :accent_deep_color,
                :accent_soft_color,
                :promo_strip_bg_color,
                :promo_strip_text_color,
                :button_primary_bg,
                :button_primary_text,
                :button_secondary_bg,
                :button_secondary_text,
                :footer_bg_color,
                :footer_text_color
             )',
            [
                'scope' => (string) ($settings['scope'] ?? self::DEFAULT_SCOPE),
                'active_preset_id' => $settings['active_preset_id'] ?? null,
                'bg_color' => (string) ($settings['bg_color'] ?? ''),
                'bg_accent_color' => (string) ($settings['bg_accent_color'] ?? ''),
                'surface_color' => (string) ($settings['surface_color'] ?? ''),
                'surface_strong_color' => (string) ($settings['surface_strong_color'] ?? ''),
                'surface_soft_color' => (string) ($settings['surface_soft_color'] ?? ''),
                'line_color' => (string) ($settings['line_color'] ?? ''),
                'line_strong_color' => (string) ($settings['line_strong_color'] ?? ''),
                'text_color' => (string) ($settings['text_color'] ?? ''),
                'muted_text_color' => (string) ($settings['muted_text_color'] ?? ''),
                'accent_color' => (string) ($settings['accent_color'] ?? ''),
                'accent_deep_color' => (string) ($settings['accent_deep_color'] ?? ''),
                'accent_soft_color' => (string) ($settings['accent_soft_color'] ?? ''),
                'promo_strip_bg_color' => (string) ($settings['promo_strip_bg_color'] ?? ''),
                'promo_strip_text_color' => (string) ($settings['promo_strip_text_color'] ?? ''),
                'button_primary_bg' => (string) ($settings['button_primary_bg'] ?? ''),
                'button_primary_text' => (string) ($settings['button_primary_text'] ?? ''),
                'button_secondary_bg' => (string) ($settings['button_secondary_bg'] ?? ''),
                'button_secondary_text' => (string) ($settings['button_secondary_text'] ?? ''),
                'footer_bg_color' => (string) ($settings['footer_bg_color'] ?? ''),
                'footer_text_color' => (string) ($settings['footer_text_color'] ?? ''),
            ]
        );
    }

    private function normalizeScope(string $scope): string
    {
        $scope = trim($scope);

        return $scope !== '' ? $scope : self::DEFAULT_SCOPE;
    }

    private function sanitizeCssColorValue(string $value, string $fallback): string
    {
        $value = trim($value);

        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^hsla?\(\s*\d{1,3}(?:\.\d+)?(?:deg)?\s*,\s*\d{1,3}(?:\.\d+)?%\s*,\s*\d{1,3}(?:\.\d+)?%(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z]{3,20}$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    /**
     * @return array<string, string>|null
     */
    private function decodeJsonObject(mixed $value): ?array
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return null;
        }

        $normalized = [];

        foreach ($decoded as $key => $tokenValue) {
            if (!is_string($key)) {
                continue;
            }

            $normalized[$key] = (string) $tokenValue;
        }

        return $normalized;
    }
}
