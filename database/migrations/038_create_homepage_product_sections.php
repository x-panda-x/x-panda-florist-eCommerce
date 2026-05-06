<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS homepage_product_sections (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                section_key VARCHAR(150) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                subheading VARCHAR(255) NULL,
                cta_label VARCHAR(190) NULL,
                cta_url VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_homepage_product_sections_active_sort (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS homepage_product_section_products (
                section_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (section_id, product_id),
                CONSTRAINT fk_homepage_section_products_section
                    FOREIGN KEY (section_id) REFERENCES homepage_product_sections(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_homepage_section_products_product
                    FOREIGN KEY (product_id) REFERENCES products(id)
                    ON DELETE CASCADE,
                KEY idx_homepage_section_products_product_id (product_id),
                KEY idx_homepage_section_products_section_sort (section_id, sort_order, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS homepage_product_section_products');
        $pdo->exec('DROP TABLE IF EXISTS homepage_product_sections');
    }
};
