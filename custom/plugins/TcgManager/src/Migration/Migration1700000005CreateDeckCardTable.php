<?php declare(strict_types=1);

namespace TcgManager\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000005CreateDeckCardTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000005;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `tcg_deck_card` (
    `id` BINARY(16) NOT NULL,
    `deck_id` BINARY(16) NOT NULL,
    `card_id` BINARY(16) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `is_sideboard` TINYINT(1) NOT NULL DEFAULT 0,
    `category` VARCHAR(50) NULL DEFAULT 'main',
    `added_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tcg_deck_card` (`deck_id`, `card_id`, `is_sideboard`),
    INDEX `idx_tcg_deck_card_deck` (`deck_id`),
    INDEX `idx_tcg_deck_card_card` (`card_id`),
    INDEX `idx_tcg_deck_card_sideboard` (`is_sideboard`),
    INDEX `idx_tcg_deck_card_category` (`category`),
    CONSTRAINT `fk_tcg_deck_card_deck` FOREIGN KEY (`deck_id`) 
        REFERENCES `tcg_deck` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tcg_deck_card_card` FOREIGN KEY (`card_id`) 
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
