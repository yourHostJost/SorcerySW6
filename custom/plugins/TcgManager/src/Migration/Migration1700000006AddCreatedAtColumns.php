<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000006AddCreatedAtColumns extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000006;
    }

    public function update(Connection $connection): void
    {
        // Add created_at column to tcg_collection_card table
        $sql = <<<SQL
ALTER TABLE `tcg_collection_card` 
ADD COLUMN `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) AFTER `foil_type`;
SQL;

        $connection->executeStatement($sql);

        // Add created_at column to other tables that might need it
        $tables = [
            'tcg_collection',
            'tcg_deck',
            'tcg_deck_card'
        ];

        foreach ($tables as $table) {
            // Check if table exists and doesn't have created_at column
            $checkSql = "SHOW COLUMNS FROM `{$table}` LIKE 'created_at'";
            $result = $connection->executeQuery($checkSql)->fetchAllAssociative();
            
            if (empty($result)) {
                $alterSql = "ALTER TABLE `{$table}` ADD COLUMN `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)";
                $connection->executeStatement($alterSql);
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement destructive changes here if needed
    }
}
