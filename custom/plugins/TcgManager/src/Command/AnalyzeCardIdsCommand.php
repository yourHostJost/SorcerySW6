<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tcg:analyze-card-ids',
    description: 'Analyze card IDs for duplicates and problems'
)]
class AnalyzeCardIdsCommand extends Command
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

        $io->title('🔍 TCG Card ID Analysis');

        // Get all cards
        $criteria = new Criteria();
        $criteria->setLimit(1000);
        $allCards = $this->cardRepository->search($criteria, $context);

        $io->info("Total cards found: " . $allCards->count());

        $idLengths = [];
        $duplicateIds = [];
        $suspiciousCards = [];

        foreach ($allCards as $card) {
            $cardId = $card->getId();
            $idLength = strlen($cardId);

            // Track ID lengths
            if (!isset($idLengths[$idLength])) {
                $idLengths[$idLength] = 0;
            }
            $idLengths[$idLength]++;

            // Track duplicate IDs
            if (!isset($duplicateIds[$cardId])) {
                $duplicateIds[$cardId] = [];
            }
            $duplicateIds[$cardId][] = [
                'title' => $card->getTitle(),
                'apiId' => $card->getApiSource(),
                'hasProduct' => $card->getShopwareProductId() !== null
            ];

            // Flag suspicious cards (short IDs)
            if ($idLength < 32) {
                $suspiciousCards[] = [
                    'id' => $cardId,
                    'title' => $card->getTitle(),
                    'length' => $idLength,
                    'apiId' => $card->getApiSource(),
                    'hasProduct' => $card->getShopwareProductId() !== null
                ];
            }
        }

        // Show ID length distribution
        $io->section('ID Length Distribution');
        $io->table(
            ['ID Length', 'Count'],
            array_map(function($length, $count) {
                return [$length, $count];
            }, array_keys($idLengths), array_values($idLengths))
        );

        // Show suspicious cards
        if (count($suspiciousCards) > 0) {
            $io->section('Suspicious Cards (Short IDs)');
            $io->table(
                ['Card ID', 'Title', 'Length', 'API ID', 'Has Product'],
                array_map(function($card) {
                    return [
                        $card['id'],
                        substr($card['title'], 0, 30) . (strlen($card['title']) > 30 ? '...' : ''),
                        $card['length'],
                        $card['apiId'] ?? 'N/A',
                        $card['hasProduct'] ? 'Yes' : 'No'
                    ];
                }, array_slice($suspiciousCards, 0, 20))
            );

            if (count($suspiciousCards) > 20) {
                $io->text("... and " . (count($suspiciousCards) - 20) . " more suspicious cards");
            }
        }

        // Show duplicates
        $realDuplicates = array_filter($duplicateIds, function($cards) {
            return count($cards) > 1;
        });

        if (count($realDuplicates) > 0) {
            $io->section('Duplicate IDs');
            foreach (array_slice($realDuplicates, 0, 10) as $id => $cards) {
                $io->text("ID: {$id} ({" . count($cards) . " cards})");
                foreach ($cards as $card) {
                    $io->text("  - {$card['title']} (API: {$card['apiId']}, Product: " . ($card['hasProduct'] ? 'Yes' : 'No') . ")");
                }
                $io->newLine();
            }
        }

        // Summary
        $io->section('Summary');
        $io->info("Total cards: " . $allCards->count());
        $io->info("Suspicious cards (short IDs): " . count($suspiciousCards));
        $io->info("Duplicate ID groups: " . count($realDuplicates));

        if (count($suspiciousCards) > 0 || count($realDuplicates) > 0) {
            $io->warning("Found problematic cards that should be cleaned up!");
            $io->note("Use 'tcg:fix-duplicate-cards --fix --reimport' to clean up and reimport");
        } else {
            $io->success("All card IDs look good!");
        }

        return Command::SUCCESS;
    }
}
