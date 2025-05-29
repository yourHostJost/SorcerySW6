<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tcg:test:cards',
    description: 'Test and display imported card data'
)]
class TestCardsCommand extends Command
{
    private EntityRepository $cardRepository;

    public function __construct(EntityRepository $cardRepository)
    {
        parent::__construct();
        $this->cardRepository = $cardRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('🃏 TCG Manager - Card Data Test');

        // Test 1: Show sample Sorcery cards
        $io->section('Sample Sorcery Cards');
        $this->showSampleCards($io, $context);

        // Test 2: Show statistics by edition
        $io->section('Cards by Edition');
        $this->showEditionStats($io, $context);

        // Test 3: Show cards with different rarities
        $io->section('Cards by Rarity');
        $this->showRarityStats($io, $context);

        // Test 4: Show cards with game mechanics
        $io->section('Sample Cards with Game Mechanics');
        $this->showGameMechanics($io, $context);

        return Command::SUCCESS;
    }

    private function showSampleCards(SymfonyStyle $io, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', 'sorcery'));
        $criteria->addSorting(new FieldSorting('title', FieldSorting::ASCENDING));
        $criteria->setLimit(10);

        $cards = $this->cardRepository->search($criteria, $context);

        $tableData = [];
        foreach ($cards as $card) {
            $tableData[] = [
                $card->getTitle(),
                $card->getEdition(),
                $card->getRarity() ?? 'N/A',
                $card->getCost() ?? 'N/A',
                $card->getAttack() ?? 'N/A',
                $card->getDefence() ?? 'N/A',
                $card->getElements() ?? 'N/A',
            ];
        }

        $io->table(
            ['Name', 'Edition', 'Rarity', 'Cost', 'Attack', 'Defence', 'Elements'],
            $tableData
        );
    }

    private function showEditionStats(SymfonyStyle $io, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', 'sorcery'));
        
        $allCards = $this->cardRepository->search($criteria, $context);
        
        $editionStats = [];
        foreach ($allCards as $card) {
            $edition = $card->getEdition();
            if (!isset($editionStats[$edition])) {
                $editionStats[$edition] = 0;
            }
            $editionStats[$edition]++;
        }

        $tableData = [];
        foreach ($editionStats as $edition => $count) {
            $tableData[] = [$edition, $count];
        }

        $io->table(['Edition', 'Card Count'], $tableData);
    }

    private function showRarityStats(SymfonyStyle $io, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', 'sorcery'));
        
        $allCards = $this->cardRepository->search($criteria, $context);
        
        $rarityStats = [];
        foreach ($allCards as $card) {
            $rarity = $card->getRarity() ?? 'Unknown';
            if (!isset($rarityStats[$rarity])) {
                $rarityStats[$rarity] = 0;
            }
            $rarityStats[$rarity]++;
        }

        $tableData = [];
        foreach ($rarityStats as $rarity => $count) {
            $tableData[] = [$rarity, $count];
        }

        $io->table(['Rarity', 'Card Count'], $tableData);
    }

    private function showGameMechanics(SymfonyStyle $io, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', 'sorcery'));
        $criteria->addSorting(new FieldSorting('cost', FieldSorting::DESCENDING));
        $criteria->setLimit(5);

        $cards = $this->cardRepository->search($criteria, $context);

        $tableData = [];
        foreach ($cards as $card) {
            $thresholds = $card->getThresholds();
            $thresholdStr = 'N/A';
            if (is_array($thresholds)) {
                $parts = [];
                foreach ($thresholds as $element => $value) {
                    if ($value > 0) {
                        $parts[] = "$element: $value";
                    }
                }
                $thresholdStr = implode(', ', $parts);
            }

            $tableData[] = [
                $card->getTitle(),
                $card->getCost() ?? 'N/A',
                $card->getAttack() ?? 'N/A',
                $card->getDefence() ?? 'N/A',
                $card->getLife() ?? 'N/A',
                $thresholdStr,
                $card->getSubTypes() ?? 'N/A',
            ];
        }

        $io->table(
            ['Name', 'Cost', 'Attack', 'Defence', 'Life', 'Thresholds', 'Sub Types'],
            $tableData
        );
    }
}
