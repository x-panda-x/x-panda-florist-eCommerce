<?php $themeSettings = is_array($themeSettings ?? null) ? $themeSettings : []; ?>
<?php $themePresets = is_array($themePresets ?? null) ? $themePresets : []; ?>

<div class="admin-form-shell">
    <p class="admin-kicker">Website</p>
    <h2 class="admin-title">Theme Colors</h2>
    <p class="admin-subtitle">Update live storefront colors. Save once after changes to apply.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <nav class="admin-section-nav" aria-label="Theme sections">
        <ul class="admin-section-nav__list">
            <li><a href="#theme-presets">Preset</a></li>
            <li><a href="#theme-surface-colors">Surface Colors</a></li>
            <li><a href="#theme-action-colors">Action Colors</a></li>
        </ul>
    </nav>

    <form method="post" action="/admin/theme" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>

        <div id="theme-presets" class="admin-field" style="grid-column:1 / -1;">
            <label for="active_preset_id">Active Preset</label>
            <select id="active_preset_id" name="active_preset_id">
                <option value="">No preset selected</option>
                <?php foreach ($themePresets as $preset): ?>
                    <option value="<?php echo htmlspecialchars((string) ($preset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($themeSettings['active_preset_id'] ?? 0) === (int) ($preset['id'] ?? 0) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) ($preset['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="theme-surface-colors" class="admin-card admin-form-section admin-form-section--website" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Core Surface Colors</h3>
            <p class="admin-section-subtitle">Set base backgrounds, text, and border colors used across most pages.</p>
            <div class="admin-grid cols-3" style="margin-top:1rem;">
                <?php foreach ([
                    'bg_color' => 'Background',
                    'bg_accent_color' => 'Background Accent',
                    'surface_color' => 'Surface',
                    'surface_strong_color' => 'Surface Strong',
                    'surface_soft_color' => 'Surface Soft',
                    'line_color' => 'Line',
                    'line_strong_color' => 'Line Strong',
                    'text_color' => 'Text',
                    'muted_text_color' => 'Muted Text',
                ] as $field => $label): ?>
                    <div class="admin-field" style="margin-bottom:0;">
                        <label for="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <div class="admin-swatch-input">
                            <input id="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>" type="text" value="<?php echo htmlspecialchars((string) ($themeSettings[$field] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="#ffffff">
                            <span class="admin-swatch-input__chip" style="background:<?php echo htmlspecialchars((string) ($themeSettings[$field] ?? '#ffffff'), ENT_QUOTES, 'UTF-8'); ?>;"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="theme-action-colors" class="admin-card admin-form-section admin-form-section--content" style="grid-column:1 / -1;padding:1.1rem;">
            <h3 class="admin-section-title">Accent And Action Colors</h3>
            <p class="admin-section-subtitle">Set button, promo, accent, and footer colors.</p>
            <div class="admin-grid cols-3" style="margin-top:1rem;">
                <?php foreach ([
                    'accent_color' => 'Accent',
                    'accent_deep_color' => 'Accent Deep',
                    'accent_soft_color' => 'Accent Soft',
                    'promo_strip_bg_color' => 'Promo Strip Background',
                    'promo_strip_text_color' => 'Promo Strip Text',
                    'button_primary_bg' => 'Primary Button Background',
                    'button_primary_text' => 'Primary Button Text',
                    'button_secondary_bg' => 'Secondary Button Background',
                    'button_secondary_text' => 'Secondary Button Text',
                    'footer_bg_color' => 'Footer Background',
                    'footer_text_color' => 'Footer Text',
                ] as $field => $label): ?>
                    <div class="admin-field" style="margin-bottom:0;">
                        <label for="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <div class="admin-swatch-input">
                            <input id="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>" type="text" value="<?php echo htmlspecialchars((string) ($themeSettings[$field] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="#ffffff">
                            <span class="admin-swatch-input__chip" style="background:<?php echo htmlspecialchars((string) ($themeSettings[$field] ?? '#ffffff'), ENT_QUOTES, 'UTF-8'); ?>;"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="grid-column:1 / -1;">
            <button type="submit" class="admin-button">Save Theme Settings</button>
        </div>
    </form>
</div>
