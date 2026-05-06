<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $this->addColumnIfMissing(
            $pdo,
            'delivery_date',
            'ALTER TABLE orders ADD COLUMN delivery_date DATE NULL AFTER delivery_address'
        );
        $this->addColumnIfMissing(
            $pdo,
            'delivery_time_slot',
            'ALTER TABLE orders ADD COLUMN delivery_time_slot VARCHAR(50) NULL AFTER delivery_date'
        );
        $this->addColumnIfMissing(
            $pdo,
            'delivery_instructions',
            'ALTER TABLE orders ADD COLUMN delivery_instructions TEXT NULL AFTER delivery_time_slot'
        );
    }

    public function down(\PDO $pdo): void
    {
        $this->dropColumnIfPresent($pdo, 'delivery_instructions');
        $this->dropColumnIfPresent($pdo, 'delivery_time_slot');
        $this->dropColumnIfPresent($pdo, 'delivery_date');
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
