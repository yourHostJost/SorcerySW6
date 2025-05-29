<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\MediaUploadService;

#[AsCommand(
    name: 'tcg:upload-media',
    description: 'Upload TCG card images to Shopware media system'
)]
class UploadMediaCommand extends Command
{
    private MediaUploadService $mediaUploadService;
    private EntityRepository $cardRepository;

    public function __construct(
        MediaUploadService $mediaUploadService,
        EntityRepository $cardRepository
    ) {
        parent::__construct();
        $this->mediaUploadService = $mediaUploadService;
        $this->cardRepository = $cardRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of cards to process',
                20
            )
            ->addOption(
                'edition',
                null,
                InputOption::VALUE_OPTIONAL,
                'Process only specific edition (Alpha, Beta, Arthurian Legends)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force upload even if images already exist'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be uploaded without actually doing it'
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

        $io->title('TCG Media Upload');

        if ($dryRun) {
            $io->note('DRY RUN MODE - No uploads will be performed');
        }

        // Build criteria for cards with products and image mappings
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        
        // Only cards that have been synced to products
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));

        // Only cards that have image mappings
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('imageMapping', null)]
        ));

        if ($edition) {
            $criteria->addFilter(new EqualsFilter('edition', $edition));
            $io->info("Filtering by edition: {$edition}");
        }

        // Get cards to process
        $cards = $this->cardRepository->search($criteria, $context);
        $totalCards = $cards->getTotal();

        if ($totalCards === 0) {
            $io->success('No cards found with products and image mappings');
            return Command::SUCCESS;
        }

        $io->info("Found {$totalCards} cards with products to process");

        if ($dryRun) {
            $io->table(
                ['Card ID', 'Title', 'Product ID', 'Available Images'],
                array_map(function ($card) {
                    $imageMapping = $card->getImageMapping() ?? [];
                    $availableImages = 0;
                    
                    foreach ($imageMapping as $finish => $data) {
                        if (is_array($data) && isset($data['exists']) && $data['exists']) {
                            $availableImages++;
                        }
                    }
                    
                    return [
                        substr($card->getId(), 0, 8),
                        $card->getTitle(),
                        $card->getShopwareProductId() ? substr($card->getShopwareProductId(), 0, 8) : 'None',
                        $availableImages
                    ];
                }, $cards->getElements())
            );

            return Command::SUCCESS;
        }

        // Process cards
        $progressBar = $io->createProgressBar($totalCards);
        $progressBar->start();

        $results = [
            'success' => 0,
            'errors' => 0,
            'totalImages' => 0,
            'errorDetails' => []
        ];

        foreach ($cards as $card) {
            try {
                $imageMapping = $card->getImageMapping() ?? [];
                $productId = $card->getShopwareProductId();

                if (!$productId) {
                    $results['errors']++;
                    $results['errorDetails'][] = [
                        'card' => $card->getTitle(),
                        'error' => 'No product ID found'
                    ];
                    continue;
                }

                // Filter out upload results from previous runs
                $cleanImageMapping = [];
                foreach ($imageMapping as $key => $value) {
                    if ($key !== 'uploadResult' && $key !== 'uploadedAt') {
                        $cleanImageMapping[$key] = $value;
                    }
                }

                if (empty($cleanImageMapping)) {
                    $results['errors']++;
                    $results['errorDetails'][] = [
                        'card' => $card->getTitle(),
                        'error' => 'No image mapping found'
                    ];
                    continue;
                }

                $uploadResult = $this->mediaUploadService->uploadCardImages(
                    $productId,
                    $cleanImageMapping,
                    $context
                );

                if ($uploadResult['success']) {
                    $results['success']++;
                    $results['totalImages'] += $uploadResult['totalUploaded'];
                } else {
                    $results['errors']++;
                    $results['errorDetails'][] = [
                        'card' => $card->getTitle(),
                        'error' => implode(', ', $uploadResult['errors'])
                    ];
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['errorDetails'][] = [
                    'card' => $card->getTitle(),
                    'error' => $e->getMessage()
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show results
        $io->success("Media upload completed!");
        
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total Cards', $totalCards],
                ['Successful', $results['success']],
                ['Errors', $results['errors']],
                ['Total Images Uploaded', $results['totalImages']]
            ]
        );

        if ($results['errors'] > 0) {
            $io->warning("Encountered {$results['errors']} errors:");
            foreach (array_slice($results['errorDetails'], 0, 10) as $error) {
                $io->text("- {$error['card']}: {$error['error']}");
            }
            
            if (count($results['errorDetails']) > 10) {
                $io->text("... and " . (count($results['errorDetails']) - 10) . " more errors");
            }
        }

        return $results['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
