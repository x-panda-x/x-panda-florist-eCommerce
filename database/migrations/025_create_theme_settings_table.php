<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS theme_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                scope VARCHAR(100) NOT NULL UNIQUE,
                active_preset_id INT UNSIGNED NULL,
                bg_color VARCHAR(32) NOT NULL,
                bg_accent_color VARCHAR(32) NOT NULL,
                surface_color VARCHAR(32) NOT NULL,
                surface_strong_color VARCHAR(32) NOT NULL,
                surface_soft_color VARCHAR(32) NOT NULL,
                line_color VARCHAR(32) NOT NULL,
                line_strong_color VARCHAR(32) NOT NULL,
                text_color VARCHAR(32) NOT NULL,
                muted_text_color VARCHAR(32) NOT NULL,
                accent_color VARCHAR(32) NOT NULL,
                accent_deep_color VARCHAR(32) NOT NULL,
                accent_soft_color VARCHAR(32) NOT NULL,
                promo_strip_bg_color VARCHAR(32) NOT NULL,
                promo_strip_text_color VARCHAR(32) NOT NULL,
                button_primary_bg VARCHAR(32) NOT NULL,
                button_primary_text VARCHAR(32) NOT NULL,
                button_secondary_bg VARCHAR(32) NOT NULL,
                button_secondary_text VARCHAR(32) NOT NULL,
                footer_bg_color VARCHAR(32) NOT NULL,
                footer_text_color VARCHAR(32) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_theme_settings_active_preset
                    FOREIGN KEY (active_preset_id) REFERENCES theme_presets(id)
                    ON DELETE SET NULL,
                KEY idx_theme_settings_active_preset_id (active_preset_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS theme_settings');
    }
};
