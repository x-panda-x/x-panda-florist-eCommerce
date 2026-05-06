<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS product_related_map (
                product_id INT UNSIGNED NOT NULL,
                related_product_id INT UNSIGNED NOT NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id, related_product_id),
                CONSTRAINT fk_product_related_map_product
                    FOREIGN KEY (product_id) REFERENCES products(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_product_related_map_related_product
                    FOREIGN KEY (related_product_id) REFERENCES products(id)
                    ON DELETE CASCADE,
                KEY idx_product_related_map_related_product_id (related_product_id),
                KEY idx_product_related_map_sort_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS product_related_map');
    }
};
