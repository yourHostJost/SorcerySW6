<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000006UpdateCardTableForSorcery extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000006;
    }

    public function update(Connection $connection): void
    {
        // Add new columns for Sorcery: Contested Realm support
        $sql = <<<SQL
ALTER TABLE `tcg_card` 
ADD COLUMN `cost` INT NULL AFTER `description`,
ADD COLUMN `attack` INT NULL AFTER `cost`,
ADD COLUMN `defence` INT NULL AFTER `attack`,
ADD COLUMN `life` INT NULL AFTER `defence`,
ADD COLUMN `thresholds` JSON NULL COMMENT 'Air, Earth, Fire, Water thresholds' AFTER `life`,
ADD COLUMN `elements` VARCHAR(100) NULL COMMENT 'Card elements (Air, Earth, Fire, Water)' AFTER `thresholds`,
ADD COLUMN `sub_types` VARCHAR(255) NULL COMMENT 'Card subtypes (Mortal, Beast, Dragon, etc.)' AFTER `elements`,
ADD COLUMN `release_date` DATE NULL AFTER `sub_types`,
ADD COLUMN `variant_slug` VARCHAR(100) NULL AFTER `release_date`,
ADD COLUMN `finish` VARCHAR(50) NULL COMMENT 'Standard, Foil, etc.' AFTER `variant_slug`,
ADD COLUMN `product` VARCHAR(50) NULL COMMENT 'Booster, Deck, Promo, etc.' AFTER `finish`,
ADD COLUMN `artist` VARCHAR(255) NULL AFTER `product`,
ADD COLUMN `flavor_text` LONGTEXT NULL AFTER `artist`,
ADD COLUMN `type_text` VARCHAR(500) NULL AFTER `flavor_text`,
ADD COLUMN `api_source` VARCHAR(50) NULL DEFAULT 'sorcery' COMMENT 'Source API (sorcery, mtg, etc.)' AFTER `stock_quantity`,
ADD COLUMN `external_id` VARCHAR(100) NULL COMMENT 'External API identifier' AFTER `api_source`,
ADD COLUMN `last_api_update` DATETIME(3) NULL AFTER `external_id`;
SQL;

        $connection->executeStatement($sql);

        // Add new indexes for performance
        $indexSql = <<<SQL
ALTER TABLE `tcg_card`
ADD INDEX `idx_tcg_card_cost` (`cost`),
ADD INDEX `idx_tcg_card_elements` (`elements`),
ADD INDEX `idx_tcg_card_sub_types` (`sub_types`),
ADD INDEX `idx_tcg_card_finish` (`finish`),
ADD INDEX `idx_tcg_card_product` (`product`),
ADD INDEX `idx_tcg_card_artist` (`artist`),
ADD INDEX `idx_tcg_card_api_source` (`api_source`),
ADD INDEX `idx_tcg_card_external_id` (`external_id`),
ADD INDEX `idx_tcg_card_last_update` (`last_api_update`),
ADD UNIQUE KEY `uk_tcg_card_api` (`api_source`, `external_id`, `variant_slug`);
SQL;

        $connection->executeStatement($indexSql);

        // Update existing records to have api_source = 'legacy' for backward compatibility
        $updateSql = <<<SQL
UPDATE `tcg_card` 
SET `api_source` = 'legacy', 
    `cost` = `threshold_cost`,
    `last_api_update` = NOW()
WHERE `api_source` IS NULL;
SQL;

        $connection->executeStatement($updateSql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Remove legacy columns if needed in future
        // This is intentionally left empty for now to maintain backward compatibility
    }
}
