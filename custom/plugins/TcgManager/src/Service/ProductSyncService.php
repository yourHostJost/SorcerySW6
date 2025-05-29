<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use TcgManager\Core\Content\Card\CardEntity;
use Psr\Log\LoggerInterface;

class ProductSyncService
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;
    private EntityRepository $mediaRepository;
    private EntityRepository $taxRepository;
    private EntityRepository $salesChannelRepository;
    private MediaUploadService $mediaUploadService;
    private LoggerInterface $logger;
    private string $projectRoot;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository,
        EntityRepository $mediaRepository,
        EntityRepository $taxRepository,
        EntityRepository $salesChannelRepository,
        MediaUploadService $mediaUploadService,
        LoggerInterface $logger,
        string $projectRoot
    ) {
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
        $this->mediaRepository = $mediaRepository;
        $this->taxRepository = $taxRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->mediaUploadService = $mediaUploadService;
        $this->logger = $logger;
        $this->projectRoot = $projectRoot;
    }

    /**
     * Sync a single card to Shopware product
     */
    public function syncCardToProduct(CardEntity $card, Context $context): array
    {
        try {
            // 1. Check if product already exists
            if ($card->getShopwareProductId()) {
                $existingProduct = $this->getProductById($card->getShopwareProductId(), $context);
                if ($existingProduct) {
                    return $this->updateExistingProduct($card, $existingProduct, $context);
                }
            }

            // 2. Create new product
            return $this->createNewProduct($card, $context);

        } catch (\Exception $e) {
            $this->logger->error('Failed to sync card to product', [
                'cardId' => $card->getId(),
                'cardTitle' => $card->getTitle(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cardId' => $card->getId()
            ];
        }
    }

    /**
     * Create new Shopware product from card
     */
    private function createNewProduct(CardEntity $card, Context $context): array
    {
        $productId = Uuid::randomHex();

        // Generate product number based on card
        $productNumber = $this->generateProductNumber($card);

        // Map card images
        $imageMapping = $this->mapCardImages($card);

        // Calculate price based on rarity
        $price = $this->calculatePrice($card);

        $productData = [
            'id' => $productId,
            'productNumber' => $productNumber,
            'name' => $card->getTitle(),
            'description' => $this->generateProductDescription($card),
            'price' => [
                [
                    'currencyId' => $this->getDefaultCurrencyId($context),
                    'gross' => $price,
                    'net' => $price / 1.19, // 19% VAT
                    'linked' => true
                ]
            ],
            'stock' => $card->getStockQuantity() ?? 0,
            'taxId' => $this->getDefaultTaxId($context),
            'active' => true,
            'visibilities' => [
                [
                    'salesChannelId' => $this->getDefaultSalesChannelId($context),
                    'visibility' => 30 // ProductVisibilityDefinition::VISIBILITY_ALL
                ]
            ],
            'customFields' => [
                'tcg_card_id' => $card->getId(),
                'tcg_edition' => $card->getEdition(),
                'tcg_rarity' => $card->getRarity(),
                'tcg_cost' => $card->getCost(),
                'tcg_attack' => $card->getAttack(),
                'tcg_defence' => $card->getDefence(),
                'tcg_elements' => $card->getElements(),
                'tcg_card_type' => $card->getCardType(),
                'tcg_artist' => $card->getArtist(),
            ]
        ];

        // Create product
        $this->productRepository->create([$productData], $context);

        // Upload and associate images
        $uploadResult = $this->mediaUploadService->uploadCardImages($productId, $imageMapping, $context);

        // Update card with product reference and upload results
        $this->cardRepository->update([
            [
                'id' => $card->getId(),
                'shopwareProductId' => $productId,
                'imageMapping' => array_merge($imageMapping, [
                    'uploadResult' => $uploadResult,
                    'uploadedAt' => (new \DateTime())->format('Y-m-d H:i:s')
                ]),
                'updatedAt' => new \DateTime()
            ]
        ], $context);

        $this->logger->info('Created new product for card', [
            'cardId' => $card->getId(),
            'productId' => $productId,
            'productNumber' => $productNumber
        ]);

        return [
            'success' => true,
            'productId' => $productId,
            'productNumber' => $productNumber,
            'cardId' => $card->getId(),
            'imageMapping' => $imageMapping,
            'uploadResult' => $uploadResult,
            'action' => 'created'
        ];
    }

    /**
     * Update existing Shopware product
     */
    private function updateExistingProduct(CardEntity $card, array $product, Context $context): array
    {
        $productId = $card->getShopwareProductId();

        // Update product data
        $updateData = [
            'id' => $productId,
            'name' => $card->getTitle(),
            'description' => $this->generateProductDescription($card),
            'stock' => $card->getStockQuantity() ?? 0,
            'customFields' => array_merge($product['customFields'] ?? [], [
                'tcg_card_id' => $card->getId(),
                'tcg_edition' => $card->getEdition(),
                'tcg_rarity' => $card->getRarity(),
                'tcg_cost' => $card->getCost(),
                'tcg_attack' => $card->getAttack(),
                'tcg_defence' => $card->getDefence(),
                'tcg_elements' => $card->getElements(),
                'tcg_card_type' => $card->getCardType(),
                'tcg_artist' => $card->getArtist(),
            ])
        ];

        $this->productRepository->update([$updateData], $context);

        $this->logger->info('Updated existing product for card', [
            'cardId' => $card->getId(),
            'productId' => $productId
        ]);

        return [
            'success' => true,
            'productId' => $productId,
            'cardId' => $card->getId(),
            'action' => 'updated'
        ];
    }

    /**
     * Map card images to finish variants
     */
    private function mapCardImages(CardEntity $card): array
    {
        $imageMapping = [];
        $cardName = $this->normalizeCardName($card->getTitle());
        $edition = $card->getEdition();

        // Define finish codes and their descriptions
        $finishCodes = [
            'b_f' => 'Base Foil',
            'b_s' => 'Base Standard',
            'bt_f' => 'Borderless Foil',
            'bt_s' => 'Borderless Standard',
            'p_f' => 'Promo Foil',
            'p_s' => 'Promo Standard',
            'pp_s' => 'Promo Pack Standard',
            'd_s' => 'Deck Standard',
            'dk_s' => 'Deck Standard',
            'sk_f' => 'Sketch Foil',
            'sk_s' => 'Sketch Standard'
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

        $this->logger->debug('Looking for image', [
            'cardName' => $cardName,
            'edition' => $edition,
            'finishCode' => $finishCode,
            'imageName' => $imageName,
            'imagePath' => $imagePath,
            'fullPath' => $fullPath,
            'exists' => file_exists($fullPath)
        ]);

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
        // Convert to lowercase and replace spaces with underscores
        $normalized = strtolower($cardName);
        $normalized = str_replace([' ', "'", '"', '/', '\\', ':', '?', '*', '!', '@', '#', '$', '%', '^', '&', '(', ')', '+', '=', '[', ']', '{', '}', '|', ';', ',', '.', '<', '>'], '_', $normalized);
        // Remove multiple underscores
        $normalized = preg_replace('/_+/', '_', $normalized);
        // Remove leading/trailing underscores
        $normalized = trim($normalized, '_');

        return $normalized;
    }

    /**
     * Generate product number
     */
    private function generateProductNumber(CardEntity $card): string
    {
        $edition = strtoupper(substr($card->getEdition(), 0, 3));
        $cardName = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $card->getTitle()), 0, 8));
        $cardId = substr(str_replace('-', '', $card->getId()), 0, 8);
        return "TCG-{$edition}-{$cardName}-{$cardId}";
    }

    /**
     * Calculate price based on rarity
     */
    private function calculatePrice(CardEntity $card): float
    {
        $basePrice = $card->getMarketPrice() ?? 0.0;

        if ($basePrice > 0) {
            return $basePrice;
        }

        // Fallback pricing based on rarity
        $rarityPricing = [
            'Ordinary' => 0.50,
            'Elite' => 1.00,
            'Exceptional' => 2.50,
            'Unique' => 5.00
        ];

        return $rarityPricing[$card->getRarity()] ?? 1.00;
    }

    /**
     * Generate product description
     */
    private function generateProductDescription(CardEntity $card): string
    {
        $description = "<h3>{$card->getTitle()}</h3>\n";
        $description .= "<p><strong>Edition:</strong> {$card->getEdition()}</p>\n";
        $description .= "<p><strong>Rarity:</strong> {$card->getRarity()}</p>\n";

        if ($card->getCardType()) {
            $description .= "<p><strong>Type:</strong> {$card->getCardType()}</p>\n";
        }

        if ($card->getCost() !== null) {
            $description .= "<p><strong>Cost:</strong> {$card->getCost()}</p>\n";
        }

        if ($card->getAttack() !== null && $card->getDefence() !== null) {
            $description .= "<p><strong>Attack/Defence:</strong> {$card->getAttack()}/{$card->getDefence()}</p>\n";
        }

        if ($card->getElements()) {
            $description .= "<p><strong>Elements:</strong> {$card->getElements()}</p>\n";
        }

        if ($card->getDescription()) {
            $description .= "<p><strong>Description:</strong></p>\n";
            $description .= "<p>{$card->getDescription()}</p>\n";
        }

        if ($card->getArtist()) {
            $description .= "<p><em>Art by {$card->getArtist()}</em></p>\n";
        }

        return $description;
    }

    /**
     * Get product by ID
     */
    private function getProductById(string $productId, Context $context): ?array
    {
        $criteria = new Criteria([$productId]);
        $result = $this->productRepository->search($criteria, $context);

        return $result->first() ? $result->first()->jsonSerialize() : null;
    }

    /**
     * Get default currency ID
     */
    private function getDefaultCurrencyId(Context $context): string
    {
        // Use system default currency
        return $context->getCurrencyId();
    }

    /**
     * Get default tax ID
     */
    private function getDefaultTaxId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $result = $this->taxRepository->search($criteria, $context);
        $tax = $result->first();

        if ($tax) {
            return $tax->getId();
        }

        // Fallback - create a default tax if none exists
        $taxId = Uuid::randomHex();
        $this->taxRepository->create([
            [
                'id' => $taxId,
                'name' => 'Standard',
                'taxRate' => 19.0
            ]
        ], $context);

        return $taxId;
    }

    /**
     * Get default sales channel ID
     */
    private function getDefaultSalesChannelId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $result = $this->salesChannelRepository->search($criteria, $context);
        $salesChannel = $result->first();

        if ($salesChannel) {
            return $salesChannel->getId();
        }

        // Fallback - should not happen in a proper Shopware installation
        throw new \RuntimeException('No sales channel found');
    }
}
