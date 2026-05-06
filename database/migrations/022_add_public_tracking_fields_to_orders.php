<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $this->addColumnIfMissing(
            $pdo,
            'tracking_status_label',
            'ALTER TABLE orders ADD COLUMN tracking_status_label VARCHAR(190) NULL AFTER status'
        );
        $this->addColumnIfMissing(
            $pdo,
            'tracking_public_note',
            'ALTER TABLE orders ADD COLUMN tracking_public_note TEXT NULL AFTER tracking_status_label'
        );
        $this->addColumnIfMissing(
            $pdo,
            'status_updated_at',
            'ALTER TABLE orders ADD COLUMN status_updated_at DATETIME NULL AFTER tracking_public_note'
        );
    }

    public function down(\PDO $pdo): void
    {
        $this->dropColumnIfPresent($pdo, 'status_updated_at');
        $this->dropColumnIfPresent($pdo, 'tracking_public_note');
        $this->dropColumnIfPresent($pdo, 'tracking_status_label');
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
