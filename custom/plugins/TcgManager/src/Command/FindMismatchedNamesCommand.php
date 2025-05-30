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
    name: 'tcg:find-mismatched-names',
    description: 'Find cards with mismatched file names'
)]
class FindMismatchedNamesCommand extends Command
{
    private EntityRepository $cardRepository;
    private string $projectRoot;

    public function __construct(
        EntityRepository $cardRepository,
        string $projectRoot
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->projectRoot = $projectRoot;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('🔍 Find Mismatched Card Names');

        // Get all cards
        $criteria = new Criteria();
        $criteria->setLimit(1000);
        $cards = $this->cardRepository->search($criteria, $context);

        $mismatches = [];
        $totalCards = 0;
        $cardsWithImages = 0;

        foreach ($cards as $card) {
            $totalCards++;
            $normalizedName = $this->normalizeCardName($card->getTitle());
            $edition = $card->getEdition();
            
            // Check if normalized name finds images
            $foundImages = false;
            $finishCodes = ['b_f', 'b_s'];
            
            foreach ($finishCodes as $finishCode) {
                $imagePath = "card_images/{$edition}/{$finishCode}/{$normalizedName}_{$finishCode}.png";
                $fullPath = $this->projectRoot . '/' . $imagePath;
                
                if (file_exists($fullPath)) {
                    $foundImages = true;
                    break;
                }
            }
            
            if ($foundImages) {
                $cardsWithImages++;
            } else {
                // Try to find actual files for this card
                $actualFiles = [];
                foreach ($finishCodes as $finishCode) {
                    $directory = $this->projectRoot . "/card_images/{$edition}/{$finishCode}/";
                    if (is_dir($directory)) {
                        // Look for files that might match this card
                        $pattern = $this->createSearchPattern($card->getTitle());
                        $files = glob($directory . "*{$pattern}*_{$finishCode}.png", GLOB_NOSORT);
                        foreach ($files as $file) {
                            $actualFiles[] = basename($file);
                        }
                    }
                }
                
                if (!empty($actualFiles)) {
                    $mismatches[] = [
                        'title' => $card->getTitle(),
                        'edition' => $edition,
                        'normalized' => $normalizedName,
                        'actualFiles' => array_unique($actualFiles)
                    ];
                }
            }
        }

        $io->section('📊 Summary');
        $io->text("Total cards: {$totalCards}");
        $io->text("Cards with images: {$cardsWithImages}");
        $io->text("Cards with mismatched names: " . count($mismatches));

        if (!empty($mismatches)) {
            $io->section('🔧 Mismatched Names');
            
            foreach ($mismatches as $mismatch) {
                $io->text("Card: {$mismatch['title']} ({$mismatch['edition']})");
                $io->text("  Normalized: {$mismatch['normalized']}");
                $io->text("  Actual files found:");
                foreach ($mismatch['actualFiles'] as $file) {
                    $actualName = preg_replace('/_(b_f|b_s|bt_f|bt_s)\.png$/', '', $file);
                    $io->text("    - {$file} → {$actualName}");
                }
                $io->newLine();
            }
            
            // Generate mapping suggestions
            $io->section('💡 Suggested Name Mappings');
            foreach ($mismatches as $mismatch) {
                if (!empty($mismatch['actualFiles'])) {
                    $firstFile = $mismatch['actualFiles'][0];
                    $actualName = preg_replace('/_(b_f|b_s|bt_f|bt_s)\.png$/', '', $firstFile);
                    $io->text("'{$mismatch['title']}' => '{$actualName}',");
                }
            }
        }

        return Command::SUCCESS;
    }

    private function normalizeCardName(string $cardName): string
    {
        // Convert to lowercase and replace spaces with underscores
        $normalized = strtolower($cardName);
        $normalized = str_replace([' ', '-', "'", '"', '/', '\\', ':', '?', '*', '!', '@', '#', '$', '%', '^', '&', '(', ')', '+', '=', '[', ']', '{', '}', '|', ';', ',', '.', '<', '>'], '_', $normalized);
        // Remove multiple underscores
        $normalized = preg_replace('/_+/', '_', $normalized);
        // Remove leading/trailing underscores
        $normalized = trim($normalized, '_');

        return $normalized;
    }

    private function createSearchPattern(string $cardName): string
    {
        // Create a more flexible search pattern
        $pattern = strtolower($cardName);
        $pattern = preg_replace('/[^a-z0-9]/', '*', $pattern);
        $pattern = preg_replace('/\*+/', '*', $pattern);
        $pattern = trim($pattern, '*');
        
        return $pattern;
    }
}
