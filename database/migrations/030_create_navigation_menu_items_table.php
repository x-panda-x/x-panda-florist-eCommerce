<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS navigation_menu_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                navigation_menu_id INT UNSIGNED NOT NULL,
                parent_id INT UNSIGNED NULL,
                label VARCHAR(190) NOT NULL,
                url VARCHAR(255) NULL,
                item_type VARCHAR(80) NOT NULL DEFAULT "link",
                target VARCHAR(40) NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                meta_json JSON NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_navigation_menu_items_menu
                    FOREIGN KEY (navigation_menu_id) REFERENCES navigation_menus(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_navigation_menu_items_parent
                    FOREIGN KEY (parent_id) REFERENCES navigation_menu_items(id)
                    ON DELETE SET NULL,
                KEY idx_navigation_menu_items_parent_id (parent_id),
                KEY idx_navigation_menu_items_item_type (item_type),
                KEY idx_navigation_menu_items_menu_enabled_sort (navigation_menu_id, is_enabled, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS navigation_menu_items');
    }
};
