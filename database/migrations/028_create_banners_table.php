<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS banners (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                banner_key VARCHAR(150) NOT NULL UNIQUE,
                page_key VARCHAR(120) NOT NULL,
                placement VARCHAR(120) NOT NULL,
                title VARCHAR(255) NULL,
                subtitle VARCHAR(255) NULL,
                body_text TEXT NULL,
                cta_label VARCHAR(190) NULL,
                cta_url VARCHAR(255) NULL,
                media_asset_id INT UNSIGNED NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_banners_media_asset
                    FOREIGN KEY (media_asset_id) REFERENCES media_assets(id)
                    ON DELETE SET NULL,
                KEY idx_banners_page_key (page_key),
                KEY idx_banners_placement (placement),
                KEY idx_banners_media_asset_id (media_asset_id),
                KEY idx_banners_page_placement_enabled_sort (page_key, placement, is_enabled, sort_order),
                KEY idx_banners_schedule (starts_at, ends_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS banners');
    }
};
