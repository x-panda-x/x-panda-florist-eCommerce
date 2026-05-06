<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $columns = $pdo->query('SHOW COLUMNS FROM customer_reminders')->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        if (!in_array('status', $columns, true)) {
            $pdo->exec(
                "ALTER TABLE customer_reminders
                 ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'active' AFTER note,
                 ADD KEY idx_customer_reminders_status (status)"
            );
        }

        if (!in_array('upcoming_notice_sent_at', $columns, true)) {
            $pdo->exec(
                'ALTER TABLE customer_reminders
                 ADD COLUMN upcoming_notice_sent_at DATETIME NULL AFTER last_sent_at,
                 ADD KEY idx_customer_reminders_upcoming_notice_sent_at (upcoming_notice_sent_at)'
            );
        }

        if (!in_array('action_required_by', $columns, true)) {
            $pdo->exec(
                'ALTER TABLE customer_reminders
                 ADD COLUMN action_required_by DATETIME NULL AFTER upcoming_notice_sent_at,
                 ADD KEY idx_customer_reminders_action_required_by (action_required_by)'
            );
        }

        if (!in_array('expired_at', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders ADD COLUMN expired_at DATETIME NULL AFTER action_required_by');
        }

        if (!in_array('cancelled_at', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders ADD COLUMN cancelled_at DATETIME NULL AFTER expired_at');
        }

        $pdo->exec(
            "UPDATE customer_reminders
             SET status = CASE
                 WHEN order_id IS NOT NULL THEN 'purchased'
                 WHEN is_active = 1 THEN 'active'
                 ELSE 'draft'
             END
             WHERE status IS NULL OR status = ''"
        );
    }

    public function down(\PDO $pdo): void
    {
        $columns = $pdo->query('SHOW COLUMNS FROM customer_reminders')->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        if (in_array('cancelled_at', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN cancelled_at');
        }

        if (in_array('expired_at', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN expired_at');
        }

        if (in_array('action_required_by', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP INDEX idx_customer_reminders_action_required_by');
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN action_required_by');
        }

        if (in_array('upcoming_notice_sent_at', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP INDEX idx_customer_reminders_upcoming_notice_sent_at');
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN upcoming_notice_sent_at');
        }

        if (in_array('status', $columns, true)) {
            $pdo->exec('ALTER TABLE customer_reminders DROP INDEX idx_customer_reminders_status');
            $pdo->exec('ALTER TABLE customer_reminders DROP COLUMN status');
        }
    }
};
