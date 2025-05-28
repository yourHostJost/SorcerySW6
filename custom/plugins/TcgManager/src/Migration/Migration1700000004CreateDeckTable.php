<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000004CreateDeckTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000004;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `tcg_deck` (
    `id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `format` VARCHAR(50) NULL,
    `archetype` VARCHAR(100) NULL,
    `colors` VARCHAR(20) NULL,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `is_complete` TINYINT(1) NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `total_cards` INT NULL DEFAULT 0,
    `main_deck_size` INT NULL DEFAULT 0,
    `sideboard_size` INT NULL DEFAULT 0,
    `source_url` VARCHAR(500) NULL,
    `source_type` VARCHAR(50) NULL DEFAULT 'user',
    `external_id` VARCHAR(100) NULL,
    `tags` JSON NULL,
    `metadata` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_tcg_deck_customer` (`customer_id`),
    INDEX `idx_tcg_deck_format` (`format`),
    INDEX `idx_tcg_deck_archetype` (`archetype`),
    INDEX `idx_tcg_deck_colors` (`colors`),
    INDEX `idx_tcg_deck_public` (`is_public`),
    INDEX `idx_tcg_deck_featured` (`is_featured`),
    INDEX `idx_tcg_deck_complete` (`is_complete`),
    INDEX `idx_tcg_deck_source_type` (`source_type`),
    INDEX `idx_tcg_deck_external_id` (`external_id`),
    CONSTRAINT `fk_tcg_deck_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement destructive changes here if needed
    }
}
