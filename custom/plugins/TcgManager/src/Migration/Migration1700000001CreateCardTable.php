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
    `title` VARCHAR(255) NOT NULL,
    `edition` VARCHAR(255) NOT NULL,
    `threshold_cost` INT NOT NULL,
    `mana_cost` VARCHAR(50) NULL,
    `rarity` VARCHAR(50) NULL,
    `card_type` VARCHAR(100) NULL,
    `description` LONGTEXT NULL,
    `image_url` VARCHAR(500) NULL,
    `set_code` VARCHAR(20) NULL,
    `card_number` VARCHAR(20) NULL,
    `market_price` DECIMAL(10,2) NULL,
    `stock_quantity` INT NULL DEFAULT 0,
    `metadata` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_tcg_card_edition` (`edition`),
    INDEX `idx_tcg_card_rarity` (`rarity`),
    INDEX `idx_tcg_card_type` (`card_type`),
    INDEX `idx_tcg_card_threshold_cost` (`threshold_cost`),
    INDEX `idx_tcg_card_set_code` (`set_code`),
    INDEX `idx_tcg_card_stock` (`stock_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement destructive changes here if needed
    }
}
