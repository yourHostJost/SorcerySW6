<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use TcgManager\Core\Content\Card\CardCollection;

class CardService
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository
    ) {
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Create a new card
     */
    public function createCard(array $cardData, Context $context = null): string
    {
        $context = $context ?? Context::createDefaultContext();
        $cardId = Uuid::randomHex();

        $cardData['id'] = $cardId;
        $cardData['createdAt'] = new \DateTime();

        $this->cardRepository->create([$cardData], $context);

        return $cardId;
    }

    /**
     * Search cards by various criteria
     */
    public function searchCards(
        ?string $searchTerm = null,
        ?string $edition = null,
        ?string $rarity = null,
        ?string $cardType = null,
        ?int $minThresholdCost = null,
        ?int $maxThresholdCost = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        bool $inStockOnly = false,
        int $limit = 20,
        int $offset = 0,
        Context $context = null,
        ?string $elements = null,
        ?int $minCost = null,
        ?int $maxCost = null,
        bool $randomOrder = false
    ): CardCollection {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();

        // Search term filter (title, description)
        if ($searchTerm) {
            $criteria->addFilter(new ContainsFilter('title', $searchTerm));
        }

        // Edition filter
        if ($edition) {
            $criteria->addFilter(new EqualsFilter('edition', $edition));
        }

        // Rarity filter
        if ($rarity) {
            $criteria->addFilter(new EqualsFilter('rarity', $rarity));
        }

        // Card type filter
        if ($cardType) {
            $criteria->addFilter(new ContainsFilter('cardType', $cardType));
        }

        // Threshold cost range (legacy)
        if ($minThresholdCost !== null || $maxThresholdCost !== null) {
            $criteria->addFilter(new RangeFilter('thresholdCost', [
                RangeFilter::GTE => $minThresholdCost,
                RangeFilter::LTE => $maxThresholdCost,
            ]));
        }

        // Sorcery cost range (new)
        if ($minCost !== null || $maxCost !== null) {
            $criteria->addFilter(new RangeFilter('cost', [
                RangeFilter::GTE => $minCost,
                RangeFilter::LTE => $maxCost,
            ]));
        }

        // Elements filter
        if ($elements) {
            $criteria->addFilter(new ContainsFilter('elements', $elements));
        }

        // Price range
        if ($minPrice !== null || $maxPrice !== null) {
            $criteria->addFilter(new RangeFilter('marketPrice', [
                RangeFilter::GTE => $minPrice,
                RangeFilter::LTE => $maxPrice,
            ]));
        }

        // In stock only
        if ($inStockOnly) {
            $criteria->addFilter(new RangeFilter('stockQuantity', [
                RangeFilter::GT => 0,
            ]));
        }

        // Sorting
        if ($randomOrder) {
            // For random order, we'll shuffle after fetching
            $criteria->addSorting(new FieldSorting('id', FieldSorting::ASCENDING));
        } else {
            $criteria->addSorting(new FieldSorting('title', FieldSorting::ASCENDING));
        }

        // For random order, fetch more cards and then randomly select
        if ($randomOrder) {
            // Fetch more cards to have a better random selection
            $criteria->setLimit($limit * 5); // Fetch 5x more cards
            $criteria->setOffset($offset);
        } else {
            // Normal pagination
            $criteria->setLimit($limit);
            $criteria->setOffset($offset);
        }

        $result = $this->cardRepository->search($criteria, $context);
        $entities = $result->getEntities();

        // If random order is requested, shuffle and limit the results
        if ($randomOrder && $entities->count() > 0) {
            $cards = $entities->getElements();

            // Use microtime for better randomness
            mt_srand((int) (microtime(true) * 1000000));
            shuffle($cards);

            // Take only the requested number of cards
            $cards = array_slice($cards, 0, $limit);

            $entities = new CardCollection($cards);
        }

        return $entities;
    }

    /**
     * Get total count of cards
     */
    public function getTotalCardCount(Context $context = null): int
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->setLimit(1); // We only need the count

        $result = $this->cardRepository->search($criteria, $context);
        return $result->getTotal();
    }

    /**
     * Get cards by edition
     */
    public function getCardsByEdition(string $edition, Context $context = null): CardCollection
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('edition', $edition));
        $criteria->addSorting(new FieldSorting('cardNumber', FieldSorting::ASCENDING));

        $result = $this->cardRepository->search($criteria, $context);

        return $result->getEntities();
    }

    /**
     * Get all available editions
     */
    public function getAvailableEditions(Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addGroupField('edition');
        $criteria->addSorting(new FieldSorting('edition', FieldSorting::ASCENDING));

        $result = $this->cardRepository->search($criteria, $context);

        $editions = [];
        foreach ($result->getEntities() as $card) {
            $edition = $card->getEdition();
            if (!in_array($edition, $editions)) {
                $editions[] = $edition;
            }
        }

        return $editions;
    }

    /**
     * Get all available rarities
     */
    public function getAvailableRarities(Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addGroupField('rarity');
        $criteria->addSorting(new FieldSorting('rarity', FieldSorting::ASCENDING));

        $result = $this->cardRepository->search($criteria, $context);

        $rarities = [];
        foreach ($result->getEntities() as $card) {
            $rarity = $card->getRarity();
            if ($rarity && !in_array($rarity, $rarities)) {
                $rarities[] = $rarity;
            }
        }

        return $rarities;
    }

    /**
     * Get all available card types
     */
    public function getAvailableCardTypes(Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addGroupField('cardType');
        $criteria->addSorting(new FieldSorting('cardType', FieldSorting::ASCENDING));

        $result = $this->cardRepository->search($criteria, $context);

        $cardTypes = [];
        foreach ($result->getEntities() as $card) {
            $cardType = $card->getCardType();
            if ($cardType && !in_array($cardType, $cardTypes)) {
                $cardTypes[] = $cardType;
            }
        }

        return $cardTypes;
    }

    /**
     * Update card stock quantity
     */
    public function updateCardStock(string $cardId, int $newQuantity, Context $context = null): bool
    {
        $context = $context ?? Context::createDefaultContext();

        try {
            $this->cardRepository->update([
                [
                    'id' => $cardId,
                    'stockQuantity' => $newQuantity,
                    'updatedAt' => new \DateTime(),
                ]
            ], $context);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cards that are in stock
     */
    public function getCardsInStock(int $limit = 50, Context $context = null): CardCollection
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('stockQuantity', [
            RangeFilter::GT => 0,
        ]));
        $criteria->addSorting(new FieldSorting('stockQuantity', FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        $result = $this->cardRepository->search($criteria, $context);

        return $result->getEntities();
    }

    /**
     * Get random featured cards
     */
    public function getFeaturedCards(int $limit = 10, Context $context = null): CardCollection
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new RangeFilter('stockQuantity', [
            RangeFilter::GT => 0,
        ]));
        $criteria->addFilter(new RangeFilter('marketPrice', [
            RangeFilter::GTE => 1.00, // Only cards worth at least $1
        ]));
        $criteria->setLimit($limit * 2); // Get more to randomize

        $result = $this->cardRepository->search($criteria, $context);
        $cards = $result->getEntities()->getElements();

        // Shuffle and limit
        shuffle($cards);
        $featuredCards = array_slice($cards, 0, $limit);

        return new CardCollection($featuredCards);
    }
}
