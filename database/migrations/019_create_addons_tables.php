<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS addons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_addons_is_active (is_active),
                KEY idx_addons_sort_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS product_addon_map (
                product_id INT UNSIGNED NOT NULL,
                addon_id INT UNSIGNED NOT NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id, addon_id),
                CONSTRAINT fk_product_addon_map_product
                    FOREIGN KEY (product_id) REFERENCES products(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_product_addon_map_addon
                    FOREIGN KEY (addon_id) REFERENCES addons(id)
                    ON DELETE CASCADE,
                KEY idx_product_addon_map_addon_id (addon_id),
                KEY idx_product_addon_map_sort_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS order_addons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                order_item_id INT UNSIGNED NULL,
                addon_id INT UNSIGNED NULL,
                addon_name VARCHAR(255) NOT NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_order_addons_order
                    FOREIGN KEY (order_id) REFERENCES orders(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_order_addons_order_item
                    FOREIGN KEY (order_item_id) REFERENCES order_items(id)
                    ON DELETE SET NULL,
                CONSTRAINT fk_order_addons_addon
                    FOREIGN KEY (addon_id) REFERENCES addons(id)
                    ON DELETE SET NULL,
                KEY idx_order_addons_order_item_id (order_item_id),
                KEY idx_order_addons_addon_id (addon_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS order_addons');
        $pdo->exec('DROP TABLE IF EXISTS product_addon_map');
        $pdo->exec('DROP TABLE IF EXISTS addons');
    }
};
