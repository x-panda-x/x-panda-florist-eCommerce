<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS customer_reminders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT UNSIGNED NOT NULL,
                occasion_label VARCHAR(120) NOT NULL,
                recipient_name VARCHAR(190) NOT NULL,
                reminder_date DATE NOT NULL,
                note TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_sent_at DATETIME NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_customer_reminders_customer
                    FOREIGN KEY (customer_id) REFERENCES customers(id)
                    ON DELETE CASCADE,
                KEY idx_customer_reminders_customer_id (customer_id),
                KEY idx_customer_reminders_reminder_date (reminder_date),
                KEY idx_customer_reminders_customer_active_date (customer_id, is_active, reminder_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS customer_reminders');
    }
};
