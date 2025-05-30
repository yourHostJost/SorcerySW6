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

#[AsCommand(
    name: 'tcg:analyze-missing-images',
    description: 'Analyze which cards are missing images'
)]
class AnalyzeMissingImagesCommand extends Command
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of cards to analyze',
                50
            )
            ->addOption(
                'show-all',
                'a',
                InputOption::VALUE_NONE,
                'Show all cards, not just missing images'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $limit = (int) $input->getOption('limit');
        $showAll = $input->getOption('show-all');

        $io->title('🖼️ TCG Missing Images Analysis');

        // Get cards with products
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));
        $criteria->setLimit($limit);
        $cards = $this->cardRepository->search($criteria, $context);

        $stats = [
            'total_cards' => 0,
            'cards_with_images' => 0,
            'cards_without_images' => 0,
            'missing_cards' => []
        ];

        foreach ($cards as $card) {
            $stats['total_cards']++;
            
            // Load product media
            $productCriteria = new Criteria([$card->getShopwareProductId()]);
            $productCriteria->addAssociation('media.media');
            $productResult = $this->productRepository->search($productCriteria, $context);
            $product = $productResult->first();

            $hasImages = false;
            $imageCount = 0;
            $finishCodes = [];

            if ($product && $product->getMedia()) {
                $imageCount = $product->getMedia()->count();
                $hasImages = $imageCount > 0;
                
                foreach ($product->getMedia() as $media) {
                    $mediaEntity = $media->getMedia();
                    if ($mediaEntity) {
                        $finishCode = $media->getCustomFields()['tcg_finish_code'] ?? 'unknown';
                        $finishCodes[] = $finishCode;
                    }
                }
            }

            if ($hasImages) {
                $stats['cards_with_images']++;
            } else {
                $stats['cards_without_images']++;
                $stats['missing_cards'][] = [
                    'id' => $card->getId(),
                    'title' => $card->getTitle(),
                    'edition' => $card->getEdition(),
                    'rarity' => $card->getRarity(),
                    'productId' => $card->getShopwareProductId()
                ];
            }

            if ($showAll || !$hasImages) {
                $io->section("Card: {$card->getTitle()}");
                $io->text("Edition: {$card->getEdition()}");
                $io->text("Rarity: {$card->getRarity()}");
                $io->text("Product ID: {$card->getShopwareProductId()}");
                $io->text("Images: " . ($hasImages ? "✅ {$imageCount} images" : "❌ No images"));
                
                if ($hasImages) {
                    $io->text("Finish codes: " . implode(', ', array_unique($finishCodes)));
                }
                
                $io->newLine();
            }
        }

        // Summary
        $io->section('📊 Summary');
        $io->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total cards analyzed', $stats['total_cards'], '100%'],
                ['Cards with images', $stats['cards_with_images'], round(($stats['cards_with_images'] / $stats['total_cards']) * 100, 1) . '%'],
                ['Cards without images', $stats['cards_without_images'], round(($stats['cards_without_images'] / $stats['total_cards']) * 100, 1) . '%']
            ]
        );

        if (count($stats['missing_cards']) > 0) {
            $io->warning("Found {$stats['cards_without_images']} cards without images:");
            $io->table(
                ['Card Title', 'Edition', 'Rarity', 'Product ID'],
                array_map(function($card) {
                    return [
                        $card['title'],
                        $card['edition'],
                        $card['rarity'],
                        substr($card['productId'], 0, 16) . '...'
                    ];
                }, array_slice($stats['missing_cards'], 0, 10))
            );
            
            if (count($stats['missing_cards']) > 10) {
                $io->text("... and " . (count($stats['missing_cards']) - 10) . " more");
            }
        } else {
            $io->success("✅ All analyzed cards have images!");
        }

        return Command::SUCCESS;
    }
}
