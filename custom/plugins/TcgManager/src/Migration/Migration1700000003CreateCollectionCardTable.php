<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000003CreateCollectionCardTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000003;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `tcg_collection_card` (
    `id` BINARY(16) NOT NULL,
    `collection_id` BINARY(16) NOT NULL,
    `card_id` BINARY(16) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `condition` VARCHAR(50) NULL,
    `language` VARCHAR(10) NULL DEFAULT 'en',
    `foil_type` VARCHAR(50) NULL DEFAULT 'normal',
    `added_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tcg_collection_card` (`collection_id`, `card_id`, `condition`, `language`, `foil_type`),
    INDEX `idx_tcg_collection_card_collection` (`collection_id`),
    INDEX `idx_tcg_collection_card_card` (`card_id`),
    INDEX `idx_tcg_collection_card_condition` (`condition`),
    INDEX `idx_tcg_collection_card_foil` (`foil_type`),
    CONSTRAINT `fk_tcg_collection_card_collection` FOREIGN KEY (`collection_id`) 
        REFERENCES `tcg_collection` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tcg_collection_card_card` FOREIGN KEY (`card_id`) 
        REFERENCES `tcg_card` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement destructive changes here if needed
    }
}
