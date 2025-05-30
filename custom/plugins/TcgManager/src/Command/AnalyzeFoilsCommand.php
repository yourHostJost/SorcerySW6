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
    name: 'tcg:analyze-foils',
    description: 'Analyze which cards have foil vs standard finishes'
)]
class AnalyzeFoilsCommand extends Command
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
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $limit = (int) $input->getOption('limit');

        $io->title('🌟 TCG Foil Analysis');

        // Get cards with products
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));
        $criteria->setLimit($limit);
        $cards = $this->cardRepository->search($criteria, $context);

        $foilStats = [
            'total_cards' => 0,
            'cards_with_foil' => 0,
            'cards_only_standard' => 0,
            'foil_codes_found' => [],
            'standard_codes_found' => []
        ];

        foreach ($cards as $card) {
            $foilStats['total_cards']++;
            
            $io->section("Card: {$card->getTitle()}");
            
            // Load product media
            $productCriteria = new Criteria([$card->getShopwareProductId()]);
            $productCriteria->addAssociation('media.media');
            $productResult = $this->productRepository->search($productCriteria, $context);
            $product = $productResult->first();

            $foilCodes = [];
            $standardCodes = [];
            $allFinishes = [];

            if ($product && $product->getMedia()) {
                foreach ($product->getMedia() as $media) {
                    $mediaEntity = $media->getMedia();
                    if ($mediaEntity) {
                        $finishCode = $media->getCustomFields()['tcg_finish_code'] ?? 'unknown';
                        $finishName = $media->getCustomFields()['tcg_finish_name'] ?? 'Unknown';
                        
                        $allFinishes[] = $finishCode;
                        
                        // Categorize finish codes
                        if (in_array($finishCode, ['b_f', 'bt_f', 'p_f', 'sk_f'])) {
                            $foilCodes[] = $finishCode;
                            if (!in_array($finishCode, $foilStats['foil_codes_found'])) {
                                $foilStats['foil_codes_found'][] = $finishCode;
                            }
                        } else {
                            $standardCodes[] = $finishCode;
                            if (!in_array($finishCode, $foilStats['standard_codes_found'])) {
                                $foilStats['standard_codes_found'][] = $finishCode;
                            }
                        }
                    }
                }
            }

            // Remove duplicates
            $foilCodes = array_unique($foilCodes);
            $standardCodes = array_unique($standardCodes);
            $allFinishes = array_unique($allFinishes);

            $hasFoil = count($foilCodes) > 0;
            $hasOnlyStandard = count($foilCodes) === 0 && count($standardCodes) > 0;

            if ($hasFoil) {
                $foilStats['cards_with_foil']++;
            }
            if ($hasOnlyStandard) {
                $foilStats['cards_only_standard']++;
            }

            $io->text("All finishes: " . implode(', ', $allFinishes));
            $io->text("Foil codes: " . (count($foilCodes) > 0 ? implode(', ', $foilCodes) : 'None'));
            $io->text("Standard codes: " . (count($standardCodes) > 0 ? implode(', ', $standardCodes) : 'None'));
            $io->text("Has foil: " . ($hasFoil ? 'YES' : 'NO'));
            $io->text("Only standard: " . ($hasOnlyStandard ? 'YES' : 'NO'));
            $io->newLine();
        }

        // Summary
        $io->section('📊 Summary');
        $io->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total cards analyzed', $foilStats['total_cards'], '100%'],
                ['Cards with foil variants', $foilStats['cards_with_foil'], round(($foilStats['cards_with_foil'] / $foilStats['total_cards']) * 100, 1) . '%'],
                ['Cards with only standard', $foilStats['cards_only_standard'], round(($foilStats['cards_only_standard'] / $foilStats['total_cards']) * 100, 1) . '%']
            ]
        );

        $io->info("Foil codes found: " . implode(', ', $foilStats['foil_codes_found']));
        $io->info("Standard codes found: " . implode(', ', $foilStats['standard_codes_found']));

        if ($foilStats['cards_only_standard'] > 0) {
            $io->success("✅ Found {$foilStats['cards_only_standard']} cards with only standard finishes - these should NOT have foil effects");
        }

        if ($foilStats['cards_with_foil'] > 0) {
            $io->success("✨ Found {$foilStats['cards_with_foil']} cards with foil variants - these should have foil effects");
        }

        return Command::SUCCESS;
    }
}
