<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $columns = $pdo->query('SHOW COLUMNS FROM customer_reminders')->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        if (!in_array('product_id', $columns, true)) {
            $pdo->exec(
                'ALTER TABLE customer_reminders
                 ADD COLUMN product_id INT UNSIGNED NULL AFTER customer_id,
                 ADD KEY idx_customer_reminders_product_id (product_id),
                 ADD CONSTRAINT fk_customer_reminders_product
                    FOREIGN KEY (product_id) REFERENCES products(id)
                    ON DELETE RESTRICT'
            );
        }

        if (!in_array('order_id', $columns, true)) {
            $pdo->exec(
                'ALTER TABLE customer_reminders
                 ADD COLUMN order_id INT UNSIGNED NULL AFTER product_id,
                 ADD KEY idx_customer_reminders_order_id (order_id),
                 ADD CONSTRAINT fk_customer_reminders_order
                    FOREIGN KEY (order_id) REFERENCES orders(id)
                    ON DELETE RESTRICT'
            );
        }

        $pdo->exec(
            'UPDATE customer_reminders cr
             LEFT JOIN (
                 SELECT o.customer_id, MAX(o.id) AS latest_order_id
                 FROM orders o
                 WHERE o.customer_id IS NOT NULL
                 GROUP BY o.customer_id
             ) recent_orders ON recent_orders.customer_id = cr.customer_id
             LEFT JOIN orders o ON o.id = recent_orders.latest_order_id
             LEFT JOIN (
                 SELECT oi.order_id, MIN(oi.id) AS first_order_item_id
                 FROM order_items oi
                 GROUP BY oi.order_id
             ) first_items ON first_items.order_id = o.id
             LEFT JOIN order_items oi ON oi.id = first_items.first_order_item_id
             SET cr.order_id = COALESCE(cr.order_id, o.id),
                 cr.product_id = COALESCE(cr.product_id, oi.product_id)
             WHERE cr.order_id IS NULL OR cr.product_id IS NULL'
        );
    }

    public function down(\PDO $pdo): void
    {
        $columns = $pdo->query('SHOW COLUMNS FROM customer_reminders')->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        if (in_array('order_id', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP FOREIGN KEY fk_customer_reminders_order');
            $pdo->exec('ALTER TABLE customer_reminders DROP INDEX idx_customer_reminders_order_id');
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN order_id');
        }

        if (in_array('product_id', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP FOREIGN KEY fk_customer_reminders_product');
            $pdo->exec('ALTER TABLE customer_reminders DROP INDEX idx_customer_reminders_product_id');
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN product_id');
        }
    }
};
