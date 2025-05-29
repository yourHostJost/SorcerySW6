<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\ProductSyncService;

#[AsCommand(
    name: 'tcg:sync-products',
    description: 'Sync TCG cards to Shopware products'
)]
class SyncProductsCommand extends Command
{
    private ProductSyncService $productSyncService;
    private EntityRepository $cardRepository;

    public function __construct(
        ProductSyncService $productSyncService,
        EntityRepository $cardRepository
    ) {
        parent::__construct();
        $this->productSyncService = $productSyncService;
        $this->cardRepository = $cardRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of cards to sync',
                50
            )
            ->addOption(
                'edition',
                null,
                InputOption::VALUE_OPTIONAL,
                'Sync only specific edition (Alpha, Beta, Arthurian Legends)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force sync even if product already exists'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be synced without actually doing it'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $limit = (int) $input->getOption('limit');
        $edition = $input->getOption('edition');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $io->title('TCG Product Sync');

        if ($dryRun) {
            $io->note('DRY RUN MODE - No changes will be made');
        }

        // Build criteria
        $criteria = new Criteria();
        $criteria->setLimit($limit);

        if ($edition) {
            $criteria->addFilter(new EqualsFilter('edition', $edition));
            $io->info("Filtering by edition: {$edition}");
        }

        if (!$force) {
            // Only sync cards without existing product
            $criteria->addFilter(new EqualsFilter('shopwareProductId', null));
            $io->info('Only syncing cards without existing products');
        }

        // Get cards to sync
        $cards = $this->cardRepository->search($criteria, $context);
        $totalCards = $cards->getTotal();

        if ($totalCards === 0) {
            $io->success('No cards found to sync');
            return Command::SUCCESS;
        }

        $io->info("Found {$totalCards} cards to sync");

        if ($dryRun) {
            $io->table(
                ['Card ID', 'Title', 'Edition', 'Rarity', 'Has Product'],
                array_map(function ($card) {
                    return [
                        substr($card->getId(), 0, 8),
                        $card->getTitle(),
                        $card->getEdition(),
                        $card->getRarity(),
                        $card->getShopwareProductId() ? 'Yes' : 'No'
                    ];
                }, $cards->getElements())
            );

            return Command::SUCCESS;
        }

        // Sync cards
        $progressBar = $io->createProgressBar($totalCards);
        $progressBar->start();

        $results = [
            'success' => 0,
            'errors' => 0,
            'created' => 0,
            'updated' => 0,
            'errorDetails' => []
        ];

        foreach ($cards as $card) {
            $result = $this->productSyncService->syncCardToProduct($card, $context);

            if ($result['success']) {
                $results['success']++;
                if ($result['action'] === 'created') {
                    $results['created']++;
                } else {
                    $results['updated']++;
                }
            } else {
                $results['errors']++;
                $results['errorDetails'][] = [
                    'card' => $card->getTitle(),
                    'error' => $result['error']
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show results
        $io->success("Product sync completed!");

        $io->table(
            ['Metric', 'Count'],
            [
                ['Total Cards', $totalCards],
                ['Successful', $results['success']],
                ['Errors', $results['errors']],
                ['Created', $results['created']],
                ['Updated', $results['updated']]
            ]
        );

        if ($results['errors'] > 0) {
            $io->warning("Encountered {$results['errors']} errors:");
            foreach ($results['errorDetails'] as $error) {
                $io->text("- {$error['card']}: {$error['error']}");
            }
        }

        return $results['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
