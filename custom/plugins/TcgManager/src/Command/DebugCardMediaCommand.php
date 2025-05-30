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

#[AsCommand(
    name: 'tcg:debug-card-media',
    description: 'Debug media for a specific card'
)]
class DebugCardMediaCommand extends Command
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;
    private EntityRepository $mediaRepository;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository,
        EntityRepository $mediaRepository
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
        $this->mediaRepository = $mediaRepository;
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

        $io->title("🔍 Debug Card Media: {$cardName}");

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
        $io->info("Card ID: {$card->getId()}");
        $io->info("Product ID: {$card->getShopwareProductId()}");

        if (!$card->getShopwareProductId()) {
            $io->error("Card has no associated product!");
            return Command::FAILURE;
        }

        // Load product with all media associations
        $productCriteria = new Criteria([$card->getShopwareProductId()]);
        $productCriteria->addAssociation('media.media');
        $productResult = $this->productRepository->search($productCriteria, $context);
        $product = $productResult->first();

        if (!$product) {
            $io->error("Product not found!");
            return Command::FAILURE;
        }

        $io->section("Product Information");
        $io->info("Product Name: {$product->getName()}");
        $io->info("Product Number: {$product->getProductNumber()}");
        $io->info("Media Count: " . ($product->getMedia() ? $product->getMedia()->count() : 0));

        if (!$product->getMedia() || $product->getMedia()->count() === 0) {
            $io->warning("Product has no media!");
            
            // Check if media exists in media repository for this card
            $mediaCriteria = new Criteria();
            $mediaCriteria->addFilter(new ContainsFilter('fileName', strtolower(str_replace([' ', '-'], '_', $card->getTitle()))));
            $allMedia = $this->mediaRepository->search($mediaCriteria, $context);
            
            $io->info("Found {$allMedia->count()} media files with similar names in media repository");
            
            foreach ($allMedia as $media) {
                $io->text("- {$media->getFileName()} (ID: {$media->getId()})");
            }
            
            return Command::SUCCESS;
        }

        $io->section("Media Details");
        
        $mediaByFinish = [];
        
        foreach ($product->getMedia() as $productMedia) {
            $media = $productMedia->getMedia();
            if (!$media) {
                continue;
            }

            $customFields = $productMedia->getCustomFields() ?? [];
            $finishCode = $customFields['tcg_finish_code'] ?? 'unknown';
            $finishName = $customFields['tcg_finish_name'] ?? 'Unknown';

            $mediaByFinish[$finishCode] = [
                'media' => $media,
                'productMedia' => $productMedia,
                'finishName' => $finishName,
                'url' => $media->getUrl(),
                'fileName' => $media->getFileName(),
                'customFields' => $customFields
            ];

            $io->text("Media: {$media->getFileName()}");
            $io->text("  Finish Code: {$finishCode}");
            $io->text("  Finish Name: {$finishName}");
            $io->text("  URL: {$media->getUrl()}");
            $io->text("  Custom Fields: " . json_encode($customFields, JSON_PRETTY_PRINT));
            $io->newLine();
        }

        $io->section("Template Data Structure");
        $io->text("This is what the template should receive:");
        
        $templateData = [];
        foreach ($mediaByFinish as $finishCode => $data) {
            $templateData[$finishCode] = [
                'url' => $data['url'],
                'alt' => $data['media']->getAlt() ?? $card->getTitle(),
                'finish' => $data['finishName']
            ];
        }
        
        $io->text("productMedia structure:");
        $io->text(json_encode($templateData, JSON_PRETTY_PRINT));

        $io->section("Expected Files");
        $expectedFiles = [
            'b_f' => "Base Foil",
            'b_s' => "Base Standard",
            'bt_f' => "Borderless Foil", 
            'bt_s' => "Borderless Standard"
        ];

        foreach ($expectedFiles as $code => $name) {
            $status = isset($mediaByFinish[$code]) ? "✅ Found" : "❌ Missing";
            $io->text("{$code} ({$name}): {$status}");
        }

        return Command::SUCCESS;
    }
}
