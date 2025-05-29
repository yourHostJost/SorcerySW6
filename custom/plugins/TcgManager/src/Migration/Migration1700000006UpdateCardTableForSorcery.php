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
        // Check if columns already exist before adding them
        $columns = [
            'cost' => 'INT NULL',
            'attack' => 'INT NULL',
            'defence' => 'INT NULL',
            'life' => 'INT NULL',
            'thresholds' => 'JSON NULL COMMENT \'Air, Earth, Fire, Water thresholds\'',
            'elements' => 'VARCHAR(100) NULL COMMENT \'Card elements (Air, Earth, Fire, Water)\'',
            'sub_types' => 'VARCHAR(255) NULL COMMENT \'Card subtypes (Mortal, Beast, Dragon, etc.)\'',
            'release_date' => 'DATE NULL',
            'variant_slug' => 'VARCHAR(100) NULL',
            'finish' => 'VARCHAR(50) NULL COMMENT \'Standard, Foil, etc.\'',
            'product' => 'VARCHAR(50) NULL COMMENT \'Booster, Deck, Promo, etc.\'',
            'artist' => 'VARCHAR(255) NULL',
            'flavor_text' => 'LONGTEXT NULL',
            'type_text' => 'VARCHAR(500) NULL',
            'api_source' => 'VARCHAR(50) NULL DEFAULT \'sorcery\' COMMENT \'Source API (sorcery, mtg, etc.)\'',
            'external_id' => 'VARCHAR(100) NULL COMMENT \'External API identifier\'',
            'last_api_update' => 'DATETIME(3) NULL'
        ];

        foreach ($columns as $columnName => $columnDefinition) {
            $columnExists = $connection->fetchOne(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'tcg_card'
                 AND COLUMN_NAME = ?",
                [$columnName]
            );

            if (!$columnExists) {
                $sql = "ALTER TABLE `tcg_card` ADD COLUMN `{$columnName}` {$columnDefinition}";
                $connection->executeStatement($sql);
            }
        }

        // Add new indexes for performance (check if they exist first)
        $indexes = [
            'idx_tcg_card_cost' => 'cost',
            'idx_tcg_card_elements' => 'elements',
            'idx_tcg_card_sub_types' => 'sub_types',
            'idx_tcg_card_finish' => 'finish',
            'idx_tcg_card_product' => 'product',
            'idx_tcg_card_artist' => 'artist',
            'idx_tcg_card_api_source' => 'api_source',
            'idx_tcg_card_external_id' => 'external_id',
            'idx_tcg_card_last_update' => 'last_api_update'
        ];

        foreach ($indexes as $indexName => $columnName) {
            $indexExists = $connection->fetchOne(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'tcg_card'
                 AND INDEX_NAME = ?",
                [$indexName]
            );

            if (!$indexExists) {
                $sql = "ALTER TABLE `tcg_card` ADD INDEX `{$indexName}` (`{$columnName}`)";
                $connection->executeStatement($sql);
            }
        }

        // Add unique key if it doesn't exist
        $uniqueKeyExists = $connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'tcg_card'
             AND INDEX_NAME = 'uk_tcg_card_api'"
        );

        if (!$uniqueKeyExists) {
            $sql = "ALTER TABLE `tcg_card` ADD UNIQUE KEY `uk_tcg_card_api` (`api_source`, `external_id`, `variant_slug`)";
            $connection->executeStatement($sql);
        }

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
