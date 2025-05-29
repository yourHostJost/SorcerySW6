<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000006AddShopwareProductIntegration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000006;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `tcg_card` 
ADD COLUMN `shopware_product_id` CHAR(36) NULL COMMENT 'Reference to Shopware product',
ADD COLUMN `image_mapping` JSON NULL COMMENT 'Mapping of card images to finish variants',
ADD INDEX `idx_shopware_product_id` (`shopware_product_id`);
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes needed
    }
}
