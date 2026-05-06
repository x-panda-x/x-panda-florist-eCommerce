<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\ThemeService;

final class ThemeController extends BaseAdminController
{
    private ThemeService $themeService;

    /**
     * @var array<int, string>
     */
    private array $themeFieldKeys = [
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

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->themeService = new ThemeService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $this->themeService->ensureThemeFoundation();

        return $this->renderAdmin('admin-theme-settings', [
            'pageTitle' => 'Theme Settings',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'themeSettings' => $this->themeService->getThemeSettings(),
            'themePresets' => $this->themeService->listPresets(),
        ]);
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/theme');
        }

        $input = [
            'active_preset_id' => trim((string) ($_POST['active_preset_id'] ?? '')),
        ];

        foreach ($this->themeFieldKeys as $fieldKey) {
            $input[$fieldKey] = trim((string) ($_POST[$fieldKey] ?? ''));
        }

        $validationError = $this->validateThemeInput($input);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/theme');
        }

        $this->themeService->saveThemeSettings('storefront', [
            'active_preset_id' => $input['active_preset_id'] !== '' ? (int) $input['active_preset_id'] : null,
            'bg_color' => $input['bg_color'],
            'bg_accent_color' => $input['bg_accent_color'],
            'surface_color' => $input['surface_color'],
            'surface_strong_color' => $input['surface_strong_color'],
            'surface_soft_color' => $input['surface_soft_color'],
            'line_color' => $input['line_color'],
            'line_strong_color' => $input['line_strong_color'],
            'text_color' => $input['text_color'],
            'muted_text_color' => $input['muted_text_color'],
            'accent_color' => $input['accent_color'],
            'accent_deep_color' => $input['accent_deep_color'],
            'accent_soft_color' => $input['accent_soft_color'],
            'promo_strip_bg_color' => $input['promo_strip_bg_color'],
            'promo_strip_text_color' => $input['promo_strip_text_color'],
            'button_primary_bg' => $input['button_primary_bg'],
            'button_primary_text' => $input['button_primary_text'],
            'button_secondary_bg' => $input['button_secondary_bg'],
            'button_secondary_text' => $input['button_secondary_text'],
            'footer_bg_color' => $input['footer_bg_color'],
            'footer_text_color' => $input['footer_text_color'],
        ]);

        $this->flash('success', 'Theme settings updated.');
        $this->redirect('/admin/theme');
    }

    /**
     * @param array<string, string> $input
     */
    private function validateThemeInput(array $input): ?string
    {
        if ($input['active_preset_id'] !== '') {
            if (!ctype_digit($input['active_preset_id'])) {
                return 'Choose a valid theme preset.';
            }

            if ($this->themeService->findPresetById((int) $input['active_preset_id']) === null) {
                return 'The selected theme preset was not found.';
            }
        }

        foreach ($this->themeFieldKeys as $fieldKey) {
            if ($input[$fieldKey] === '') {
                return 'All theme fields are required.';
            }
        }

        return null;
    }
}
