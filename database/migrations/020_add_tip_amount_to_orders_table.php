<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE orders
             ADD COLUMN tip_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
             AFTER tax_amount'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE orders
             DROP COLUMN tip_amount'
        );
    }
};
