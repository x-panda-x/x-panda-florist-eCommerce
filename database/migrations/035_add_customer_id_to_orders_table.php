<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $this->addColumnIfMissing(
            $pdo,
            'customer_id',
            'ALTER TABLE orders ADD COLUMN customer_id INT UNSIGNED NULL AFTER public_access_token'
        );

        if (!$this->indexExists($pdo, 'orders', 'idx_orders_customer_id')) {
            $pdo->exec('ALTER TABLE orders ADD KEY idx_orders_customer_id (customer_id)');
        }

        if (!$this->foreignKeyExists($pdo, 'orders', 'fk_orders_customer')) {
            $pdo->exec(
                'ALTER TABLE orders
                 ADD CONSTRAINT fk_orders_customer
                 FOREIGN KEY (customer_id) REFERENCES customers(id)
                 ON DELETE SET NULL'
            );
        }
    }

    public function down(\PDO $pdo): void
    {
        if ($this->foreignKeyExists($pdo, 'orders', 'fk_orders_customer')) {
            $pdo->exec('ALTER TABLE orders DROP FOREIGN KEY fk_orders_customer');
        }

        if ($this->indexExists($pdo, 'orders', 'idx_orders_customer_id')) {
            $pdo->exec('ALTER TABLE orders DROP INDEX idx_orders_customer_id');
        }

        $this->dropColumnIfPresent($pdo, 'customer_id');
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

    private function indexExists(\PDO $pdo, string $table, string $index): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name'
        );
        $statement->execute([
            'table_name' => $table,
            'index_name' => $index,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function foreignKeyExists(\PDO $pdo, string $table, string $constraint): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND CONSTRAINT_NAME = :constraint_name'
        );
        $statement->execute([
            'table_name' => $table,
            'constraint_name' => $constraint,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }
};
