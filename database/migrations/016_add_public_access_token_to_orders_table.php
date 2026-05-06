<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $this->addColumnIfMissing(
            $pdo,
            'public_access_token',
            'ALTER TABLE orders ADD COLUMN public_access_token VARCHAR(64) NULL AFTER order_number'
        );

        $statement = $pdo->query(
            'SELECT id
             FROM orders
             WHERE public_access_token IS NULL OR public_access_token = \'\''
        );

        $orderIds = $statement !== false ? $statement->fetchAll(\PDO::FETCH_COLUMN) : [];
        $update = $pdo->prepare(
            'UPDATE orders
             SET public_access_token = :public_access_token
             WHERE id = :id'
        );

        foreach ($orderIds as $orderId) {
            $update->execute([
                'public_access_token' => bin2hex(random_bytes(32)),
                'id' => (int) $orderId,
            ]);
        }

        $pdo->exec('ALTER TABLE orders MODIFY COLUMN public_access_token VARCHAR(64) NOT NULL');

        if (!$this->indexExists($pdo, 'orders', 'ux_orders_public_access_token')) {
            $pdo->exec('ALTER TABLE orders ADD UNIQUE KEY ux_orders_public_access_token (public_access_token)');
        }
    }

    public function down(\PDO $pdo): void
    {
        if ($this->indexExists($pdo, 'orders', 'ux_orders_public_access_token')) {
            $pdo->exec('ALTER TABLE orders DROP INDEX ux_orders_public_access_token');
        }

        $this->dropColumnIfPresent($pdo, 'public_access_token');
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
};
