<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(32) NOT NULL UNIQUE,
                customer_name VARCHAR(190) NOT NULL,
                customer_email VARCHAR(190) NOT NULL,
                customer_phone VARCHAR(50) NOT NULL,
                recipient_name VARCHAR(190) NULL,
                delivery_address TEXT NOT NULL,
                card_message TEXT NULL,
                subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(50) NOT NULL DEFAULT \'pending\',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_orders_order_number (order_number),
                KEY idx_orders_customer_email (customer_email),
                KEY idx_orders_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS orders');
    }
};
