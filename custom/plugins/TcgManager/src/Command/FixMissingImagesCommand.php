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
use TcgManager\Service\ProductSyncService;

#[AsCommand(
    name: 'tcg:fix-missing-images',
    description: 'Fix cards that are missing images by re-importing their media'
)]
class FixMissingImagesCommand extends Command
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
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be fixed without actually doing it'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of cards to fix',
                50
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $dryRun = $input->getOption('dry-run');
        $limit = (int) $input->getOption('limit');

        $io->title('🔧 Fix Missing Images');

        // Find cards without images
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));
        $criteria->setLimit(1000); // Check all cards
        $cards = $this->cardRepository->search($criteria, $context);

        $cardsWithoutImages = [];

        foreach ($cards as $card) {
            // Load product media
            $productCriteria = new Criteria([$card->getShopwareProductId()]);
            $productCriteria->addAssociation('media.media');
            $productResult = $this->productRepository->search($productCriteria, $context);
            $product = $productResult->first();

            $hasImages = false;
            if ($product && $product->getMedia()) {
                $hasImages = $product->getMedia()->count() > 0;
            }

            if (!$hasImages) {
                $cardsWithoutImages[] = $card;
            }
        }

        $io->info("Found " . count($cardsWithoutImages) . " cards without images");

        if (count($cardsWithoutImages) === 0) {
            $io->success("✅ All cards have images!");
            return Command::SUCCESS;
        }

        // Limit the cards to fix
        $cardsToFix = array_slice($cardsWithoutImages, 0, $limit);

        $io->table(
            ['Card Title', 'Edition', 'Rarity'],
            array_map(function($card) {
                return [
                    $card->getTitle(),
                    $card->getEdition(),
                    $card->getRarity()
                ];
            }, $cardsToFix)
        );

        if ($dryRun) {
            $io->note("DRY RUN: Would fix " . count($cardsToFix) . " cards");
            return Command::SUCCESS;
        }

        if (!$io->confirm("Fix images for " . count($cardsToFix) . " cards?", true)) {
            $io->info("Cancelled");
            return Command::SUCCESS;
        }

        // Fix the images
        $successCount = 0;
        $errorCount = 0;

        foreach ($cardsToFix as $card) {
            $io->text("Fixing: {$card->getTitle()}");

            try {
                // Re-sync the product to import missing media
                $result = $this->productSyncService->syncCardToProduct($card, $context);

                if ($result['success']) {
                    $successCount++;
                    $io->text("  ✅ Success");
                } else {
                    $errorCount++;
                    $io->text("  ❌ Error: " . ($result['error'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $errorCount++;
                $io->text("  ❌ Exception: " . $e->getMessage());
            }
        }

        $io->newLine();
        $io->success("Image fix completed!");
        $io->info("Successfully fixed: {$successCount}");
        $io->info("Errors: {$errorCount}");

        if ($errorCount > 0) {
            $io->warning("Some cards could not be fixed. Check the logs for details.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
