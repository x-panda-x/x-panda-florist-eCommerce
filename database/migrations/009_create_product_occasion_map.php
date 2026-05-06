<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS product_occasion_map (
                product_id INT UNSIGNED NOT NULL,
                occasion_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id, occasion_id),
                CONSTRAINT fk_product_occasion_map_product
                    FOREIGN KEY (product_id) REFERENCES products(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_product_occasion_map_occasion
                    FOREIGN KEY (occasion_id) REFERENCES occasions(id)
                    ON DELETE CASCADE,
                KEY idx_product_occasion_map_occasion_id (occasion_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS product_occasion_map');
    }
};
