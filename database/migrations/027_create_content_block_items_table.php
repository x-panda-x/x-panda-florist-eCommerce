<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS content_block_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                content_block_id INT UNSIGNED NOT NULL,
                item_key VARCHAR(150) NULL,
                title VARCHAR(255) NULL,
                subtitle VARCHAR(255) NULL,
                body_text TEXT NULL,
                cta_label VARCHAR(190) NULL,
                cta_url VARCHAR(255) NULL,
                media_asset_id INT UNSIGNED NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                meta_json JSON NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_content_block_items_block
                    FOREIGN KEY (content_block_id) REFERENCES content_blocks(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_content_block_items_media_asset
                    FOREIGN KEY (media_asset_id) REFERENCES media_assets(id)
                    ON DELETE SET NULL,
                KEY idx_content_block_items_block_id (content_block_id),
                KEY idx_content_block_items_item_key (item_key),
                KEY idx_content_block_items_media_asset_id (media_asset_id),
                KEY idx_content_block_items_block_enabled_sort (content_block_id, is_enabled, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS content_block_items');
    }
};
