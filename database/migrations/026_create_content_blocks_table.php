<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS content_blocks (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                block_key VARCHAR(150) NOT NULL UNIQUE,
                page_key VARCHAR(120) NOT NULL,
                name VARCHAR(190) NOT NULL,
                block_type VARCHAR(100) NOT NULL,
                heading VARCHAR(255) NULL,
                subheading VARCHAR(255) NULL,
                body_text TEXT NULL,
                cta_label VARCHAR(190) NULL,
                cta_url VARCHAR(255) NULL,
                media_asset_id INT UNSIGNED NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                meta_json JSON NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_content_blocks_media_asset
                    FOREIGN KEY (media_asset_id) REFERENCES media_assets(id)
                    ON DELETE SET NULL,
                KEY idx_content_blocks_page_key (page_key),
                KEY idx_content_blocks_block_type (block_type),
                KEY idx_content_blocks_media_asset_id (media_asset_id),
                KEY idx_content_blocks_page_enabled_sort (page_key, is_enabled, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS content_blocks');
    }
};
