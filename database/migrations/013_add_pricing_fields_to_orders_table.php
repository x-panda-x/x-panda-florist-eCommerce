<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $this->addColumnIfMissing(
            $pdo,
            'delivery_zip',
            'ALTER TABLE orders ADD COLUMN delivery_zip VARCHAR(20) NULL AFTER delivery_address'
        );
        $this->addColumnIfMissing(
            $pdo,
            'delivery_fee',
            'ALTER TABLE orders ADD COLUMN delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER subtotal'
        );
        $this->addColumnIfMissing(
            $pdo,
            'tax_amount',
            'ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER delivery_fee'
        );
        $this->addColumnIfMissing(
            $pdo,
            'total_amount',
            'ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tax_amount'
        );
    }

    public function down(\PDO $pdo): void
    {
        $this->dropColumnIfPresent($pdo, 'total_amount');
        $this->dropColumnIfPresent($pdo, 'tax_amount');
        $this->dropColumnIfPresent($pdo, 'delivery_fee');
        $this->dropColumnIfPresent($pdo, 'delivery_zip');
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
