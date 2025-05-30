<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\ProductSyncService;

#[AsCommand(
    name: 'tcg:fix-product-numbers',
    description: 'Fix products with conflicting numbers from duplicate card IDs'
)]
class FixProductNumbersCommand extends Command
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;
    private ProductSyncService $productSyncService;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository,
        ProductSyncService $productSyncService
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
        $this->productSyncService = $productSyncService;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'fix',
                'f',
                InputOption::VALUE_NONE,
                'Actually fix the problematic products'
            )
            ->addOption(
                'card-prefix',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Fix products for cards with this ID prefix',
                '01971e4b'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $fix = $input->getOption('fix');
        $cardPrefix = $input->getOption('card-prefix');

        $io->title('🔧 Fix Product Numbers for Conflicting Cards');

        // Step 1: Find cards with the problematic ID prefix
        $io->section('Step 1: Finding problematic cards');

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('id', $cardPrefix));
        $problematicCards = $this->cardRepository->search($criteria, $context);

        $io->info("Found {$problematicCards->count()} cards with ID prefix '{$cardPrefix}'");

        $cardsWithProducts = [];
        $cardsWithoutProducts = [];

        foreach ($problematicCards as $card) {
            if ($card->getShopwareProductId()) {
                $cardsWithProducts[] = $card;
            } else {
                $cardsWithoutProducts[] = $card;
            }
        }

        $io->table(
            ['Card ID', 'Title', 'Has Product'],
            array_map(function($card) {
                return [
                    substr($card->getId(), 0, 16) . '...',
                    $card->getTitle(),
                    $card->getShopwareProductId() ? 'Yes' : 'No'
                ];
            }, iterator_to_array($problematicCards))
        );

        $io->info("Cards with products: " . count($cardsWithProducts));
        $io->info("Cards without products: " . count($cardsWithoutProducts));

        // Step 2: Find conflicting products
        $io->section('Step 2: Finding conflicting products');

        $productCriteria = new Criteria();
        $productCriteria->addFilter(new ContainsFilter('productNumber', "TCG-"));
        $productCriteria->addFilter(new ContainsFilter('productNumber', $cardPrefix));
        $conflictingProducts = $this->productRepository->search($productCriteria, $context);

        $io->info("Found {$conflictingProducts->count()} products with conflicting numbers");

        if ($conflictingProducts->count() > 0) {
            $io->table(
                ['Product ID', 'Product Number', 'Name'],
                array_map(function($product) {
                    return [
                        substr($product->getId(), 0, 16) . '...',
                        $product->getProductNumber(),
                        $product->getName()
                    ];
                }, iterator_to_array($conflictingProducts))
            );
        }

        if (!$fix) {
            $io->note('Use --fix to delete conflicting products and recreate them');
            return Command::SUCCESS;
        }

        // Step 3: Delete conflicting products
        if ($conflictingProducts->count() > 0) {
            $io->section('Step 3: Deleting conflicting products');

            if ($io->confirm("Delete {$conflictingProducts->count()} conflicting products?", false)) {
                $deleteData = [];
                foreach ($conflictingProducts as $product) {
                    $deleteData[] = ['id' => $product->getId()];
                }

                $this->productRepository->delete($deleteData, $context);
                $io->success("Deleted {$conflictingProducts->count()} conflicting products");

                // Update cards to remove product references
                $updateData = [];
                foreach ($cardsWithProducts as $card) {
                    $updateData[] = [
                        'id' => $card->getId(),
                        'shopwareProductId' => null
                    ];
                }

                if (count($updateData) > 0) {
                    $this->cardRepository->update($updateData, $context);
                    $io->info("Updated " . count($updateData) . " cards to remove product references");
                }
            } else {
                $io->info('Deletion cancelled');
                return Command::SUCCESS;
            }
        }

        // Step 4: Recreate products with unique numbers
        $io->section('Step 4: Recreating products');

        $allProblematicCards = array_merge($cardsWithProducts, $cardsWithoutProducts);
        $successCount = 0;
        $errorCount = 0;

        foreach ($allProblematicCards as $card) {
            try {
                $io->text("Syncing: {$card->getTitle()}");
                $result = $this->productSyncService->syncCardToProduct($card, $context);

                if ($result['success']) {
                    $successCount++;
                    $io->text("  ✓ Success");
                } else {
                    $errorCount++;
                    $io->text("  ✗ Error: " . ($result['error'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $errorCount++;
                $io->text("  ✗ Exception: " . $e->getMessage());
            }
        }

        $io->success("Product recreation completed!");
        $io->info("Successful: {$successCount}");
        $io->info("Errors: {$errorCount}");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
