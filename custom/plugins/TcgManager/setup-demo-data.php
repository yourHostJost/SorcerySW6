<?php declare(strict_types=1);

/**
 * Demo Data Setup Script for TcgManager Plugin
 * 
 * This script can be run to quickly set up demo data for testing the plugin.
 * Run from Shopware root directory: php custom/plugins/TcgManager/setup-demo-data.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use TcgManager\DataFixtures\CardFixtures;
use TcgManager\Service\CardService;

echo "🎮 TcgManager Demo Data Setup\n";
echo "=============================\n\n";

try {
    // Load environment
    if (file_exists(__DIR__ . '/../../../.env')) {
        (new Dotenv())->load(__DIR__ . '/../../../.env');
    }

    // Boot Shopware kernel
    $kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? false));
    $kernel->boot();
    $container = $kernel->getContainer();

    echo "✅ Shopware kernel loaded\n";

    // Get required services
    $cardRepository = $container->get('tcg_card.repository');
    $productRepository = $container->get('product.repository');
    
    if (!$cardRepository || !$productRepository) {
        throw new \Exception('Required repositories not found. Is the TcgManager plugin installed and activated?');
    }

    $cardService = new CardService($cardRepository, $productRepository);
    $cardFixtures = new CardFixtures($cardService);
    
    echo "✅ Services initialized\n";

    // Check if demo data already exists
    $context = Context::createDefaultContext();
    $existingCards = $cardRepository->search(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria(), $context);
    
    if ($existingCards->getTotal() > 0) {
        echo "⚠️  Demo data already exists ({$existingCards->getTotal()} cards found)\n";
        echo "Do you want to continue and add more demo data? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 'y') {
            echo "❌ Setup cancelled\n";
            exit(0);
        }
    }

    // Load demo cards
    echo "📦 Loading demo card data...\n";
    $cardIds = $cardFixtures->loadSampleCards($context);
    
    echo "✅ Created " . count($cardIds) . " demo cards:\n";
    
    // Display created cards
    $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria($cardIds);
    $cards = $cardRepository->search($criteria, $context);
    
    foreach ($cards as $card) {
        $price = $card->getMarketPrice() ? number_format($card->getMarketPrice(), 2) . '€' : 'N/A';
        $stock = $card->getStockQuantity() ?? 0;
        echo "  - {$card->getTitle()} ({$card->getEdition()}) - {$price} - Stock: {$stock}\n";
    }

    echo "\n🎉 Demo data setup completed successfully!\n\n";
    
    echo "Next steps:\n";
    echo "1. Register a new customer account in your shop\n";
    echo "2. Navigate to 'My Account' > 'Card Collections'\n";
    echo "3. Search for cards and add them to your collection\n";
    echo "4. Create decks and compare them with your collection\n\n";
    
    echo "API endpoints to test:\n";
    echo "- GET /api/tcg/cards/search?q=Lightning\n";
    echo "- GET /api/tcg/collections (requires login)\n";
    echo "- POST /api/tcg/collections (requires login)\n\n";
    
    echo "Happy trading! 🃏✨\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure you're running this from the Shopware root directory\n";
    echo "2. Ensure the TcgManager plugin is installed and activated:\n";
    echo "   bin/console plugin:list | grep TcgManager\n";
    echo "3. Check if all database tables exist:\n";
    echo "   bin/console dbal:run-sql \"SHOW TABLES LIKE 'tcg_%'\"\n";
    echo "4. Clear cache and try again:\n";
    echo "   bin/console cache:clear\n\n";
    
    exit(1);
}
