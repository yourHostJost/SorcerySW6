<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\MediaUploadService;

#[AsCommand(
    name: 'tcg:debug-image-upload',
    description: 'Debug image upload process for a specific card'
)]
class DebugImageUploadCommand extends Command
{
    private EntityRepository $cardRepository;
    private MediaUploadService $mediaUploadService;
    private string $projectRoot;

    public function __construct(
        EntityRepository $cardRepository,
        MediaUploadService $mediaUploadService,
        string $projectRoot
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->mediaUploadService = $mediaUploadService;
        $this->projectRoot = $projectRoot;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'cardName',
                InputArgument::REQUIRED,
                'Name of the card to debug'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $cardName = $input->getArgument('cardName');

        $io->title("🔧 Debug Image Upload: {$cardName}");

        // Find the card
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('title', $cardName));
        $cards = $this->cardRepository->search($criteria, $context);

        if ($cards->count() === 0) {
            $io->error("Card '{$cardName}' not found!");
            return Command::FAILURE;
        }

        $card = $cards->first();
        $io->info("Found card: {$card->getTitle()}");
        $io->info("Product ID: {$card->getShopwareProductId()}");
        $io->info("Project Root: {$this->projectRoot}");

        if (!$card->getShopwareProductId()) {
            $io->error("Card has no product ID!");
            return Command::FAILURE;
        }

        // Test file paths manually
        $normalizedName = $this->normalizeCardName($card->getTitle());
        $io->info("Normalized name: {$normalizedName}");

        $testPaths = [
            'b_f' => "card_images/{$card->getEdition()}/b_f/{$normalizedName}_b_f.png",
            'b_s' => "card_images/{$card->getEdition()}/b_s/{$normalizedName}_b_s.png"
        ];

        $io->section("File Path Tests");
        foreach ($testPaths as $finish => $relativePath) {
            $fullPath = $this->projectRoot . '/' . $relativePath;
            $exists = file_exists($fullPath);
            $io->text("{$finish}: {$relativePath}");
            $io->text("  Full path: {$fullPath}");
            $io->text("  Exists: " . ($exists ? '✅ Yes' : '❌ No'));
            $io->newLine();
        }

        // Test image mapping
        $imageMapping = $this->mapCardImages($card);

        $io->section("Image Mapping");
        if (empty($imageMapping)) {
            $io->warning("No images found in mapping!");
            return Command::FAILURE;
        }

        foreach ($imageMapping as $finishCode => $data) {
            $io->text("✅ {$finishCode}: {$data['path']} ({$data['finish']})");
        }

        // Test upload
        $io->section("Testing Upload");

        if (!$io->confirm("Proceed with test upload?", true)) {
            return Command::SUCCESS;
        }

        try {
            $uploadResult = $this->mediaUploadService->uploadCardImages(
                $card->getShopwareProductId(),
                $imageMapping,
                $context
            );

            $io->section("Upload Results");
            $io->text("Success: " . ($uploadResult['success'] ? 'Yes' : 'No'));
            $io->text("Total uploaded: {$uploadResult['totalUploaded']}");
            $io->text("Total errors: {$uploadResult['totalErrors']}");

            if (!empty($uploadResult['uploadedImages'])) {
                $io->text("Uploaded images:");
                foreach ($uploadResult['uploadedImages'] as $finishCode => $data) {
                    $io->text("  - {$finishCode}: {$data['mediaId']}");
                }
            }

            if (!empty($uploadResult['errors'])) {
                $io->text("Errors:");
                foreach ($uploadResult['errors'] as $error) {
                    $io->text("  - {$error}");
                }
            }

        } catch (\Exception $e) {
            $io->error("Upload failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Map card images to finish variants
     */
    private function mapCardImages($card): array
    {
        $imageMapping = [];
        $cardName = $this->normalizeCardName($card->getTitle());
        $edition = $card->getEdition();

        // Define finish codes and their descriptions
        $finishCodes = [
            'b_f' => 'Base Foil',
            'b_s' => 'Base Standard',
            'bt_f' => 'Borderless Foil',
            'bt_s' => 'Borderless Standard'
        ];

        foreach ($finishCodes as $finishCode => $finishName) {
            $imagePath = $this->findImagePath($cardName, $edition, $finishCode);
            if ($imagePath) {
                $imageMapping[$finishCode] = [
                    'path' => $imagePath,
                    'finish' => $finishName,
                    'exists' => true
                ];
            }
        }

        return $imageMapping;
    }

    /**
     * Find image path for card with specific finish
     */
    private function findImagePath(string $cardName, string $edition, string $finishCode): ?string
    {
        $imageName = $cardName . '_' . $finishCode . '.png';
        $imagePath = "card_images/{$edition}/{$finishCode}/{$imageName}";
        $fullPath = $this->projectRoot . '/' . $imagePath;

        if (file_exists($fullPath)) {
            return $imagePath;
        }

        return null;
    }

    /**
     * Normalize card name for file system
     */
    private function normalizeCardName(string $cardName): string
    {
        // Special mappings for problematic card names
        $specialMappings = [
            'Maelström' => 'maelstrom',
            'Spire' => 'spire_lich',
            'Valley' => 'rift_valley',
            'Brocéliande' => 'broceliande',
            'Hunter\'s Lodge' => 'hunters_lodge',
            'Merlin\'s Tower' => 'merlins_tower',
            'Wizard\'s Den' => 'wizards_den',
            'Erik\'s Curiosa' => 'eriks_curiosa',
            'Philosopher\'s Stone' => 'philosophers_stone',
            'Älvalinne Dryads' => 'alvalinne_dryads',
            'Fisherman\'s Family' => 'fishermans_family',
            'Merlin\'s Staff' => 'merlins_staff',
            'Grösse Poltergeist' => 'grosse_poltergeist',
            'Mariner\'s Curse' => 'mariners_curse',
            'Angel\'s Egg' => 'angels_egg',
            'Devil\'s Egg' => 'devils_egg',
            'A Midsummer Night\'s Dream' => 'a_midsummer_nights_dream',
            'Castle\'s Ablaze!' => 'castles_ablaze',
            'Hamlet\'s Ablaze!' => 'hamlets_ablaze',
            'King\'s Council' => 'kings_council',
            'Courtesan Thaïs' => 'courtesan_thais',
            'Orb of Ba\'al Berith' => 'orb_of_baal_berith',
        ];

        // Check for special mapping first
        if (isset($specialMappings[$cardName])) {
            return $specialMappings[$cardName];
        }

        // Convert to lowercase and replace spaces with underscores
        $normalized = strtolower($cardName);
        $normalized = str_replace([' ', '-', "'", '"', '/', '\\', ':', '?', '*', '!', '@', '#', '$', '%', '^', '&', '(', ')', '+', '=', '[', ']', '{', '}', '|', ';', ',', '.', '<', '>'], '_', $normalized);
        // Remove multiple underscores
        $normalized = preg_replace('/_+/', '_', $normalized);
        // Remove leading/trailing underscores
        $normalized = trim($normalized, '_');

        return $normalized;
    }
}
