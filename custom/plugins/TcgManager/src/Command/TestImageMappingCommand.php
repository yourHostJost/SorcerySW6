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
    name: 'tcg:test-image-mapping',
    description: 'Test image mapping for TCG cards'
)]
class TestImageMappingCommand extends Command
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

        $io->title('TCG Image Mapping Test');

        // Get first few cards, ordered by title
        $criteria = new Criteria();
        $criteria->setLimit(3);
        $criteria->addSorting(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting('title'));

        $cards = $this->cardRepository->search($criteria, $context);

        foreach ($cards as $card) {
            $io->section("Testing card: {$card->getTitle()}");

            $normalizedName = $this->normalizeCardName($card->getTitle());
            $io->text("Normalized name: {$normalizedName}");
            $io->text("Edition: {$card->getEdition()}");

            $finishCodes = ['b_f', 'b_s', 'd_s'];
            $foundImages = [];

            foreach ($finishCodes as $finishCode) {
                $imageName = $normalizedName . '_' . $finishCode . '.png';
                $imagePath = "card_images/{$card->getEdition()}/{$finishCode}/{$imageName}";
                $fullPath = $this->projectRoot . '/' . $imagePath;

                $exists = file_exists($fullPath);

                $io->text("  {$finishCode}: {$imagePath} - " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND"));

                if ($exists) {
                    $foundImages[] = $finishCode;
                }
            }

            $io->text("Found images: " . count($foundImages));
            $io->newLine();
        }

        return Command::SUCCESS;
    }

    private function normalizeCardName(string $cardName): string
    {
        // Convert to lowercase and replace spaces with underscores
        $normalized = strtolower($cardName);
        $normalized = str_replace([' ', "'", '"', '/', '\\', ':', '?', '*', '!', '@', '#', '$', '%', '^', '&', '(', ')', '+', '=', '[', ']', '{', '}', '|', ';', ',', '.', '<', '>'], '_', $normalized);
        // Remove multiple underscores
        $normalized = preg_replace('/_+/', '_', $normalized);
        // Remove leading/trailing underscores
        $normalized = trim($normalized, '_');

        return $normalized;
    }
}
