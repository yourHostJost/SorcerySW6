<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000001CreateCardTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000001;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `tcg_card` (
    `id` BINARY(16) NOT NULL,

    -- Basic card information
    `title` VARCHAR(255) NOT NULL,
    `edition` VARCHAR(255) NOT NULL,
    `rarity` VARCHAR(50) NULL,
    `card_type` VARCHAR(100) NULL,
    `description` LONGTEXT NULL,

    -- Sorcery-specific game mechanics
    `cost` INT NULL,
    `attack` INT NULL,
    `defence` INT NULL,
    `life` INT NULL,
    `thresholds` JSON NULL COMMENT 'Air, Earth, Fire, Water thresholds',
    `elements` VARCHAR(100) NULL COMMENT 'Card elements (Air, Earth, Fire, Water)',
    `sub_types` VARCHAR(255) NULL COMMENT 'Card subtypes (Mortal, Beast, Dragon, etc.)',

    -- Set and variant information
    `release_date` DATE NULL,
    `variant_slug` VARCHAR(100) NULL,
    `finish` VARCHAR(50) NULL COMMENT 'Standard, Foil, etc.',
    `product` VARCHAR(50) NULL COMMENT 'Booster, Deck, Promo, etc.',
    `artist` VARCHAR(255) NULL,
    `flavor_text` LONGTEXT NULL,
    `type_text` VARCHAR(500) NULL,

    -- Legacy fields (for backward compatibility)
    `threshold_cost` INT NULL COMMENT 'Legacy field, use cost instead',
    `mana_cost` VARCHAR(50) NULL COMMENT 'Legacy field',
    `set_code` VARCHAR(20) NULL COMMENT 'Legacy field',
    `card_number` VARCHAR(20) NULL COMMENT 'Legacy field',

    -- Shop integration
    `image_url` VARCHAR(500) NULL,
    `market_price` DECIMAL(10,2) NULL,
    `stock_quantity` INT NULL DEFAULT 0,

    -- API integration
    `api_source` VARCHAR(50) NULL DEFAULT 'sorcery' COMMENT 'Source API (sorcery, mtg, etc.)',
    `external_id` VARCHAR(100) NULL COMMENT 'External API identifier',
    `last_api_update` DATETIME(3) NULL,

    -- Metadata and timestamps
    `metadata` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,

    PRIMARY KEY (`id`),

    -- Performance indexes
    INDEX `idx_tcg_card_title` (`title`),
    INDEX `idx_tcg_card_edition` (`edition`),
    INDEX `idx_tcg_card_rarity` (`rarity`),
    INDEX `idx_tcg_card_type` (`card_type`),
    INDEX `idx_tcg_card_cost` (`cost`),
    INDEX `idx_tcg_card_elements` (`elements`),
    INDEX `idx_tcg_card_sub_types` (`sub_types`),
    INDEX `idx_tcg_card_finish` (`finish`),
    INDEX `idx_tcg_card_product` (`product`),
    INDEX `idx_tcg_card_artist` (`artist`),
    INDEX `idx_tcg_card_api_source` (`api_source`),
    INDEX `idx_tcg_card_external_id` (`external_id`),
    INDEX `idx_tcg_card_stock` (`stock_quantity`),
    INDEX `idx_tcg_card_last_update` (`last_api_update`),

    -- Legacy indexes
    INDEX `idx_tcg_card_threshold_cost` (`threshold_cost`),
    INDEX `idx_tcg_card_set_code` (`set_code`),

    -- Unique constraint for API imports
    UNIQUE KEY `uk_tcg_card_api` (`api_source`, `external_id`, `variant_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement destructive changes here if needed
    }
}
