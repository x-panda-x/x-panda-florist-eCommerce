<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $this->addColumnIfMissing(
            $pdo,
            'policy_version',
            'ALTER TABLE orders ADD COLUMN policy_version VARCHAR(64) NULL AFTER status'
        );
        $this->addColumnIfMissing(
            $pdo,
            'policy_accepted',
            'ALTER TABLE orders ADD COLUMN policy_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER policy_version'
        );
        $this->addColumnIfMissing(
            $pdo,
            'policy_accepted_at',
            'ALTER TABLE orders ADD COLUMN policy_accepted_at DATETIME NULL AFTER policy_accepted'
        );
        $this->addColumnIfMissing(
            $pdo,
            'customer_ip',
            'ALTER TABLE orders ADD COLUMN customer_ip VARCHAR(45) NULL AFTER policy_accepted_at'
        );
        $this->addColumnIfMissing(
            $pdo,
            'user_agent',
            'ALTER TABLE orders ADD COLUMN user_agent VARCHAR(255) NULL AFTER customer_ip'
        );
    }

    public function down(\PDO $pdo): void
    {
        $this->dropColumnIfPresent($pdo, 'user_agent');
        $this->dropColumnIfPresent($pdo, 'customer_ip');
        $this->dropColumnIfPresent($pdo, 'policy_accepted_at');
        $this->dropColumnIfPresent($pdo, 'policy_accepted');
        $this->dropColumnIfPresent($pdo, 'policy_version');
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
