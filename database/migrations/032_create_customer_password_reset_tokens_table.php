<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS customer_password_reset_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT UNSIGNED NOT NULL,
                token_hash VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_customer_password_reset_tokens_customer
                    FOREIGN KEY (customer_id) REFERENCES customers(id)
                    ON DELETE CASCADE,
                KEY idx_customer_password_reset_tokens_customer_id (customer_id),
                KEY idx_customer_password_reset_tokens_expires_at (expires_at),
                KEY idx_customer_password_reset_tokens_used_at (used_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS customer_password_reset_tokens');
    }
};
