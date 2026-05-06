<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS media_assets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                collection_key VARCHAR(120) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                disk_path VARCHAR(255) NOT NULL,
                public_path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(120) NOT NULL,
                extension VARCHAR(20) NOT NULL,
                file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                alt_text VARCHAR(255) NULL,
                width INT UNSIGNED NULL,
                height INT UNSIGNED NULL,
                uploaded_by_admin_id INT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_media_assets_uploaded_by_admin
                    FOREIGN KEY (uploaded_by_admin_id) REFERENCES admins(id)
                    ON DELETE SET NULL,
                KEY idx_media_assets_collection_key (collection_key),
                KEY idx_media_assets_uploaded_by_admin_id (uploaded_by_admin_id),
                KEY idx_media_assets_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS media_assets');
    }
};
