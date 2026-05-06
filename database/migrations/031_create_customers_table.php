<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS customers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(190) NOT NULL,
                phone VARCHAR(50) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                marketing_opt_in TINYINT(1) NOT NULL DEFAULT 0,
                reminder_email_opt_in TINYINT(1) NOT NULL DEFAULT 1,
                order_email_opt_in TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at DATETIME NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_customers_is_active (is_active),
                KEY idx_customers_marketing_opt_in (marketing_opt_in),
                KEY idx_customers_reminder_email_opt_in (reminder_email_opt_in),
                KEY idx_customers_order_email_opt_in (order_email_opt_in)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS customers');
    }
};
