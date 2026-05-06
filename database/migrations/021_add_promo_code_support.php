<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS promo_codes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(64) NOT NULL UNIQUE,
                description TEXT NULL,
                discount_type VARCHAR(50) NOT NULL DEFAULT \'fixed_amount\',
                discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                minimum_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                starts_at DATETIME NULL,
                expires_at DATETIME NULL,
                usage_limit INT UNSIGNED NULL,
                times_used INT UNSIGNED NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_promo_codes_active (is_active),
                KEY idx_promo_codes_schedule (starts_at, expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->addColumnIfMissing(
            $pdo,
            'promo_code',
            'ALTER TABLE orders ADD COLUMN promo_code VARCHAR(64) NULL AFTER subtotal'
        );
        $this->addColumnIfMissing(
            $pdo,
            'promo_discount_amount',
            'ALTER TABLE orders ADD COLUMN promo_discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER promo_code'
        );
    }

    public function down(\PDO $pdo): void
    {
        $this->dropColumnIfPresent($pdo, 'promo_discount_amount');
        $this->dropColumnIfPresent($pdo, 'promo_code');
        $pdo->exec('DROP TABLE IF EXISTS promo_codes');
    }

    private function addColumnIfMissing(\PDO $pdo, string $column, string $sql): void
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $statement->execute([
            'table_name' => 'orders',
            'column_name' => $column,
        ]);

        if ((int) $statement->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }

    private function dropColumnIfPresent(\PDO $pdo, string $column): void
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $statement->execute([
            'table_name' => 'orders',
            'column_name' => $column,
        ]);

        if ((int) $statement->fetchColumn() > 0) {
            $pdo->exec('ALTER TABLE orders DROP COLUMN ' . $column);
        }
    }
};
