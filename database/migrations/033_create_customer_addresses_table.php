<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS customer_addresses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT UNSIGNED NOT NULL,
                label VARCHAR(120) NOT NULL,
                recipient_name VARCHAR(190) NOT NULL,
                delivery_address TEXT NOT NULL,
                delivery_zip VARCHAR(20) NOT NULL,
                delivery_instructions TEXT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_customer_addresses_customer
                    FOREIGN KEY (customer_id) REFERENCES customers(id)
                    ON DELETE CASCADE,
                KEY idx_customer_addresses_customer_id (customer_id),
                KEY idx_customer_addresses_customer_default (customer_id, is_default),
                KEY idx_customer_addresses_delivery_zip (delivery_zip)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS customer_addresses');
    }
};
