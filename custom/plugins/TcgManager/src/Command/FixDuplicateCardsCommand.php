<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\CardService;
use TcgManager\Service\ProductSyncService;

#[AsCommand(
    name: 'tcg:fix-duplicate-cards',
    description: 'Find and fix cards with duplicate/invalid IDs'
)]
class FixDuplicateCardsCommand extends Command
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;
    private CardService $cardService;
    private ProductSyncService $productSyncService;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository,
        CardService $cardService,
        ProductSyncService $productSyncService
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
        $this->cardService = $cardService;
        $this->productSyncService = $productSyncService;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'analyze',
                'a',
                InputOption::VALUE_NONE,
                'Only analyze and show problematic cards'
            )
            ->addOption(
                'fix',
                'f',
                InputOption::VALUE_NONE,
                'Actually fix the problematic cards'
            )
            ->addOption(
                'reimport',
                'r',
                InputOption::VALUE_NONE,
                'Reimport cards from API after fixing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $analyze = $input->getOption('analyze');
        $fix = $input->getOption('fix');
        $reimport = $input->getOption('reimport');

        $io->title('🔍 TCG Duplicate Cards Analysis & Fix');

        // Step 1: Find cards with suspicious IDs (too short, identical, etc.)
        $io->section('Step 1: Analyzing card IDs');

        $criteria = new Criteria();
        $criteria->setLimit(1000);
        $allCards = $this->cardRepository->search($criteria, $context);

        $suspiciousCards = [];
        $idCounts = [];
        $shortIds = [];

        foreach ($allCards as $card) {
            $cardId = $card->getId();
            $idLength = strlen($cardId);

            // Count ID occurrences
            if (!isset($idCounts[$cardId])) {
                $idCounts[$cardId] = [];
            }
            $idCounts[$cardId][] = $card;

            // Check for suspiciously short IDs (should be 32 chars)
            if ($idLength < 32) {
                $shortIds[] = [
                    'id' => $cardId,
                    'title' => $card->getTitle(),
                    'length' => $idLength,
                    'apiId' => $card->getApiId(),
                    'hasProduct' => $card->getShopwareProductId() !== null
                ];
            }
        }

        // Find duplicates
        $duplicates = array_filter($idCounts, function($cards) {
            return count($cards) > 1;
        });

        $io->info("Total cards analyzed: " . $allCards->count());
        $io->info("Cards with short IDs: " . count($shortIds));
        $io->info("Duplicate ID groups: " . count($duplicates));

        if (count($shortIds) > 0) {
            $io->warning("Found cards with suspiciously short IDs:");
            $io->table(
                ['Card ID', 'Title', 'ID Length', 'API ID', 'Has Product'],
                array_map(function($card) {
                    return [
                        $card['id'],
                        $card['title'],
                        $card['length'],
                        $card['apiId'] ?? 'N/A',
                        $card['hasProduct'] ? 'Yes' : 'No'
                    ];
                }, array_slice($shortIds, 0, 10))
            );
            
            if (count($shortIds) > 10) {
                $io->text("... and " . (count($shortIds) - 10) . " more");
            }
        }

        if (count($duplicates) > 0) {
            $io->warning("Found duplicate ID groups:");
            foreach (array_slice($duplicates, 0, 5) as $id => $cards) {
                $io->text("ID: {$id} - {" . count($cards) . " cards}");
                foreach ($cards as $card) {
                    $io->text("  - {$card->getTitle()} (API: {$card->getApiId()})");
                }
            }
        }

        if ($analyze) {
            return Command::SUCCESS;
        }

        if (!$fix && !$reimport) {
            $io->note('Use --fix to delete problematic cards, --reimport to reimport them');
            return Command::SUCCESS;
        }

        // Step 2: Fix problematic cards
        if ($fix) {
            $io->section('Step 2: Fixing problematic cards');

            $cardsToDelete = [];
            
            // Collect all problematic cards
            foreach ($shortIds as $cardInfo) {
                $cardsToDelete[] = $cardInfo['id'];
            }
            
            foreach ($duplicates as $id => $cards) {
                // Keep the first card, delete the rest
                for ($i = 1; $i < count($cards); $i++) {
                    $cardsToDelete[] = $cards[$i]->getId();
                }
            }

            if (count($cardsToDelete) > 0) {
                $io->warning("Will delete " . count($cardsToDelete) . " problematic cards");
                
                if ($io->confirm('Proceed with deletion?', false)) {
                    $deleteData = array_map(function($id) {
                        return ['id' => $id];
                    }, $cardsToDelete);

                    $this->cardRepository->delete($deleteData, $context);
                    $io->success("Deleted " . count($cardsToDelete) . " problematic cards");
                } else {
                    $io->info('Deletion cancelled');
                    return Command::SUCCESS;
                }
            }
        }

        // Step 3: Reimport missing cards
        if ($reimport) {
            $io->section('Step 3: Reimporting cards from API');
            
            $io->info('Starting fresh import from Sorcery API...');
            
            // Use the existing import command
            $importResult = $this->cardService->importCardsFromApi();
            
            if ($importResult['success']) {
                $io->success("Successfully imported {$importResult['imported']} cards");
                
                // Now sync products for the new cards
                $io->info('Syncing products for newly imported cards...');
                
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('shopwareProductId', null));
                $criteria->setLimit(100);
                
                $newCards = $this->cardRepository->search($criteria, $context);
                
                $syncCount = 0;
                foreach ($newCards as $card) {
                    try {
                        $syncResult = $this->productSyncService->syncCardToProduct($card, $context);
                        if ($syncResult['success']) {
                            $syncCount++;
                        }
                    } catch (\Exception $e) {
                        $io->warning("Failed to sync {$card->getTitle()}: {$e->getMessage()}");
                    }
                }
                
                $io->success("Synced {$syncCount} new products");
            } else {
                $io->error("Import failed: " . ($importResult['error'] ?? 'Unknown error'));
            }
        }

        $io->success('Card cleanup completed!');
        return Command::SUCCESS;
    }
}
