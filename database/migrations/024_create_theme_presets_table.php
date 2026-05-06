<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS theme_presets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                preset_key VARCHAR(120) NOT NULL UNIQUE,
                name VARCHAR(190) NOT NULL,
                tokens_json JSON NOT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_theme_presets_is_system (is_system),
                KEY idx_theme_presets_is_default (is_default)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS theme_presets');
    }
};
