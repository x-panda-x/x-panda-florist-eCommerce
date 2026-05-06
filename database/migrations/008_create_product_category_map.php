<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS product_category_map (
                product_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id, category_id),
                CONSTRAINT fk_product_category_map_product
                    FOREIGN KEY (product_id) REFERENCES products(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_product_category_map_category
                    FOREIGN KEY (category_id) REFERENCES categories(id)
                    ON DELETE CASCADE,
                KEY idx_product_category_map_category_id (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS product_category_map');
    }
};
