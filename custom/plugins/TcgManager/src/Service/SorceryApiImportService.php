<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SorceryApiImportService
{
    private const API_URL = 'https://api.sorcerytcg.com/api/cards';
    private const API_SOURCE = 'sorcery';

    private EntityRepository $cardRepository;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $cardRepository,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->cardRepository = $cardRepository;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Import all cards from Sorcery API
     */
    public function importAllCards(Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();
        $stats = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        try {
            $this->logger->info('Starting Sorcery API import...');

            // Fetch data from API
            $response = $this->httpClient->request('GET', self::API_URL);
            $apiData = $response->toArray();

            $stats['total'] = count($apiData);
            $this->logger->info("Fetched {$stats['total']} cards from Sorcery API");

            foreach ($apiData as $cardData) {
                try {
                    $result = $this->importCard($cardData, $context);
                    $stats[$result]++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->logger->error('Error importing card: ' . $e->getMessage(), [
                        'card' => $cardData['name'] ?? 'unknown'
                    ]);
                }
            }

            $this->logger->info('Sorcery API import completed', $stats);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch data from Sorcery API: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Import a single card with all its variants
     */
    private function importCard(array $cardData, Context $context): string
    {
        $cardName = $cardData['name'];
        $guardian = $cardData['guardian'];

        // Process each set and variant
        foreach ($cardData['sets'] as $set) {
            foreach ($set['variants'] as $variant) {
                $externalId = $this->generateExternalId($cardName, $set['name'], $variant['slug']);

                // Check if card already exists
                $existingCard = $this->findExistingCard($externalId, $variant['slug'], $context);

                if ($existingCard) {
                    // Update existing card
                    $this->updateCard($existingCard['id'], $cardData, $set, $variant, $context);
                    return 'updated';
                } else {
                    // Create new card
                    $this->createCard($cardData, $set, $variant, $context);
                    return 'imported';
                }
            }
        }

        return 'skipped';
    }

    /**
     * Create a new card from API data
     */
    private function createCard(array $cardData, array $set, array $variant, Context $context): void
    {
        $guardian = $cardData['guardian'];
        $cardId = Uuid::randomHex();
        $externalId = $this->generateExternalId($cardData['name'], $set['name'], $variant['slug']);

        $cardRecord = [
            'id' => $cardId,
            'title' => $cardData['name'],
            'edition' => $set['name'],
            'rarity' => $guardian['rarity'] ?? null,
            'cardType' => $guardian['type'] ?? null,
            'description' => $guardian['rulesText'] ?? null,

            // Sorcery-specific fields
            'cost' => $guardian['cost'] ?? null,
            'attack' => $guardian['attack'] ?? null,
            'defence' => $guardian['defence'] ?? null,
            'life' => $guardian['life'] ?? null,
            'thresholds' => $guardian['thresholds'] ?? null,
            'elements' => $cardData['elements'] ?? null,
            'subTypes' => $cardData['subTypes'] ?? null,

            // Legacy field compatibility (required for backward compatibility)
            'thresholdCost' => $guardian['cost'] ?? 0, // Map cost to legacy field

            // Set and variant information
            'releaseDate' => isset($set['releasedAt']) ? new \DateTime($set['releasedAt']) : null,
            'variantSlug' => $variant['slug'],
            'finish' => $variant['finish'] ?? null,
            'product' => $variant['product'] ?? null,
            'artist' => $variant['artist'] ?? null,
            'flavorText' => $variant['flavorText'] ?? null,
            'typeText' => $variant['typeText'] ?? null,

            // API integration
            'apiSource' => self::API_SOURCE,
            'externalId' => $externalId,
            'lastApiUpdate' => new \DateTime(),

            // Default values
            'stockQuantity' => 0,
            'marketPrice' => null,
            'createdAt' => new \DateTime(),
        ];

        $this->cardRepository->create([$cardRecord], $context);

        $this->logger->debug("Created card: {$cardData['name']} ({$variant['slug']})");
    }

    /**
     * Update an existing card with fresh API data
     */
    private function updateCard(string $cardId, array $cardData, array $set, array $variant, Context $context): void
    {
        $guardian = $cardData['guardian'];

        $updateData = [
            'id' => $cardId,
            'title' => $cardData['name'],
            'edition' => $set['name'],
            'rarity' => $guardian['rarity'] ?? null,
            'cardType' => $guardian['type'] ?? null,
            'description' => $guardian['rulesText'] ?? null,

            // Sorcery-specific fields
            'cost' => $guardian['cost'] ?? null,
            'attack' => $guardian['attack'] ?? null,
            'defence' => $guardian['defence'] ?? null,
            'life' => $guardian['life'] ?? null,
            'thresholds' => $guardian['thresholds'] ?? null,
            'elements' => $cardData['elements'] ?? null,
            'subTypes' => $cardData['subTypes'] ?? null,

            // Legacy field compatibility
            'thresholdCost' => $guardian['cost'] ?? 0,

            // Set and variant information
            'releaseDate' => isset($set['releasedAt']) ? new \DateTime($set['releasedAt']) : null,
            'artist' => $variant['artist'] ?? null,
            'flavorText' => $variant['flavorText'] ?? null,
            'typeText' => $variant['typeText'] ?? null,

            // API integration
            'lastApiUpdate' => new \DateTime(),
            'updatedAt' => new \DateTime(),
        ];

        $this->cardRepository->update([$updateData], $context);

        $this->logger->debug("Updated card: {$cardData['name']} ({$variant['slug']})");
    }

    /**
     * Find existing card by external ID and variant slug
     */
    private function findExistingCard(string $externalId, string $variantSlug, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', self::API_SOURCE));
        $criteria->addFilter(new EqualsFilter('externalId', $externalId));
        $criteria->addFilter(new EqualsFilter('variantSlug', $variantSlug));

        $result = $this->cardRepository->search($criteria, $context);

        return $result->first() ? ['id' => $result->first()->getId()] : null;
    }

    /**
     * Generate a unique external ID for the card
     */
    private function generateExternalId(string $cardName, string $setName, string $variantSlug): string
    {
        return md5($cardName . '|' . $setName . '|' . $variantSlug);
    }

    /**
     * Get import statistics
     */
    public function getImportStats(Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', self::API_SOURCE));

        $result = $this->cardRepository->search($criteria, $context);

        return [
            'total_sorcery_cards' => $result->getTotal(),
            'last_import' => $this->getLastImportDate($context),
        ];
    }

    /**
     * Get the date of the last import
     */
    private function getLastImportDate(Context $context): ?\DateTimeInterface
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', self::API_SOURCE));
        $criteria->addSorting(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting('lastApiUpdate', 'DESC'));
        $criteria->setLimit(1);

        $result = $this->cardRepository->search($criteria, $context);
        $card = $result->first();

        return $card ? $card->getLastApiUpdate() : null;
    }
}
