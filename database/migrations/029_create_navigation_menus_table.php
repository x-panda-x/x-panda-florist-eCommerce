<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS navigation_menus (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                menu_key VARCHAR(120) NOT NULL UNIQUE,
                name VARCHAR(190) NOT NULL,
                placement VARCHAR(120) NOT NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_navigation_menus_placement (placement),
                KEY idx_navigation_menus_enabled (is_enabled)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS navigation_menus');
    }
};
