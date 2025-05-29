<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use TcgManager\Core\Content\Deck\DeckEntity;
use TcgManager\Core\Content\Deck\DeckCollection;

class DeckService
{
    private EntityRepository $deckRepository;
    private EntityRepository $deckCardRepository;
    private EntityRepository $cardRepository;
    private CollectionService $collectionService;

    public function __construct(
        EntityRepository $deckRepository,
        EntityRepository $deckCardRepository,
        EntityRepository $cardRepository,
        CollectionService $collectionService
    ) {
        $this->deckRepository = $deckRepository;
        $this->deckCardRepository = $deckCardRepository;
        $this->cardRepository = $cardRepository;
        $this->collectionService = $collectionService;
    }

    /**
     * Create a new deck for a customer
     */
    public function createDeck(
        string $customerId,
        string $name,
        ?string $description = null,
        ?string $format = null,
        ?string $archetype = null,
        ?string $colors = null,
        bool $isPublic = false,
        Context $context = null
    ): string {
        $context = $context ?? Context::createDefaultContext();
        $deckId = Uuid::randomHex();

        $deckData = [
            'id' => $deckId,
            'customerId' => $customerId,
            'name' => $name,
            'description' => $description,
            'format' => $format,
            'archetype' => $archetype,
            'colors' => $colors,
            'isPublic' => $isPublic,
            'isComplete' => false,
            'isFeatured' => false,
            'totalCards' => 0,
            'mainDeckSize' => 0,
            'sideboardSize' => 0,
            'sourceType' => 'user',
            'createdAt' => new \DateTime(),
        ];

        $this->deckRepository->create([$deckData], $context);

        return $deckId;
    }

    /**
     * Get decks for a customer
     */
    public function getCustomerDecks(string $customerId, Context $context = null): DeckCollection
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('deckCards.card');

        $result = $this->deckRepository->search($criteria, $context);

        return $result->getEntities();
    }

    /**
     * Get public decks
     */
    public function getPublicDecks(int $limit = 20, Context $context = null): DeckCollection
    {
        $context = $context ?? Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isPublic', true));
        $criteria->addAssociation('deckCards.card');
        $criteria->addAssociation('customer');
        $criteria->setLimit($limit);

        $result = $this->deckRepository->search($criteria, $context);

        return $result->getEntities();
    }

    /**
     * Add a card to a deck
     */
    public function addCardToDeck(
        string $deckId,
        string $cardId,
        int $quantity = 1,
        bool $isSideboard = false,
        ?string $category = 'main',
        Context $context = null
    ): string {
        $context = $context ?? Context::createDefaultContext();

        // Check if card already exists in deck
        $existingCard = $this->getDeckCard($deckId, $cardId, $isSideboard, $context);

        if ($existingCard) {
            // Update quantity
            $this->deckCardRepository->update([
                [
                    'id' => $existingCard['id'],
                    'quantity' => $existingCard['quantity'] + $quantity,
                    'updatedAt' => new \DateTime(),
                ]
            ], $context);

            $deckCardId = $existingCard['id'];
        } else {
            // Create new deck card entry
            $deckCardId = Uuid::randomHex();

            $deckCardData = [
                'id' => $deckCardId,
                'deckId' => $deckId,
                'cardId' => $cardId,
                'quantity' => $quantity,
                'isSideboard' => $isSideboard,
                'category' => $category,
                'addedAt' => new \DateTime(),
            ];

            $this->deckCardRepository->create([$deckCardData], $context);
        }

        // Update deck statistics
        $this->updateDeckStatistics($deckId, $context);

        return $deckCardId;
    }

    /**
     * Remove a card from a deck
     */
    public function removeCardFromDeck(
        string $deckId,
        string $cardId,
        int $quantity = 1,
        bool $isSideboard = false,
        Context $context = null
    ): bool {
        $context = $context ?? Context::createDefaultContext();

        $existingCard = $this->getDeckCard($deckId, $cardId, $isSideboard, $context);

        if (!$existingCard) {
            return false;
        }

        $newQuantity = $existingCard['quantity'] - $quantity;

        if ($newQuantity <= 0) {
            // Remove the card completely
            $this->deckCardRepository->delete([['id' => $existingCard['id']]], $context);
        } else {
            // Update quantity
            $this->deckCardRepository->update([
                [
                    'id' => $existingCard['id'],
                    'quantity' => $newQuantity,
                    'updatedAt' => new \DateTime(),
                ]
            ], $context);
        }

        // Update deck statistics
        $this->updateDeckStatistics($deckId, $context);

        return true;
    }

    /**
     * Compare deck with customer's collection
     */
    public function compareDeckWithCollection(string $deckId, string $customerId, Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();

        // Get deck cards
        $deckCriteria = new Criteria();
        $deckCriteria->addFilter(new EqualsFilter('deckId', $deckId));
        $deckCriteria->addAssociation('card');
        $deckCards = $this->deckCardRepository->search($deckCriteria, $context);

        // Get customer's default collection
        $collection = $this->collectionService->getDefaultCollection($customerId, $context);

        if (!$collection) {
            // Customer has no collection, all cards are missing
            $missingCards = [];
            foreach ($deckCards as $deckCard) {
                $missingCards[] = [
                    'card' => $deckCard->getCard(),
                    'needed' => $deckCard->getQuantity(),
                    'owned' => 0,
                    'missing' => $deckCard->getQuantity(),
                    'isSideboard' => $deckCard->getIsSideboard(),
                ];
            }
            return [
                'missingCards' => $missingCards,
                'ownedCards' => [],
                'totalMissing' => count($missingCards),
            ];
        }

        // Get collection cards
        $collectionCriteria = new Criteria();
        $collectionCriteria->addFilter(new EqualsFilter('collectionId', $collection->getId()));
        $collectionCriteria->addAssociation('card');
        $collectionCards = $this->collectionCardRepository->search($collectionCriteria, $context);

        // Build collection lookup
        $collectionLookup = [];
        foreach ($collectionCards as $collectionCard) {
            $cardId = $collectionCard->getCardId();
            if (!isset($collectionLookup[$cardId])) {
                $collectionLookup[$cardId] = 0;
            }
            $collectionLookup[$cardId] += $collectionCard->getQuantity();
        }

        // Compare deck with collection
        $missingCards = [];
        $ownedCards = [];

        foreach ($deckCards as $deckCard) {
            $cardId = $deckCard->getCardId();
            $needed = $deckCard->getQuantity();
            $owned = $collectionLookup[$cardId] ?? 0;
            $missing = max(0, $needed - $owned);

            $cardData = [
                'card' => $deckCard->getCard(),
                'needed' => $needed,
                'owned' => $owned,
                'missing' => $missing,
                'isSideboard' => $deckCard->getIsSideboard(),
            ];

            if ($missing > 0) {
                $missingCards[] = $cardData;
            } else {
                $ownedCards[] = $cardData;
            }
        }

        return [
            'missingCards' => $missingCards,
            'ownedCards' => $ownedCards,
            'totalMissing' => count($missingCards),
        ];
    }

    /**
     * Get a specific deck card
     */
    private function getDeckCard(string $deckId, string $cardId, bool $isSideboard, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('deckId', $deckId),
            new EqualsFilter('cardId', $cardId),
            new EqualsFilter('isSideboard', $isSideboard),
        ]));

        $result = $this->deckCardRepository->search($criteria, $context);

        return $result->first();
    }

    /**
     * Update deck statistics
     */
    private function updateDeckStatistics(string $deckId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('deckId', $deckId));
        $deckCards = $this->deckCardRepository->search($criteria, $context);

        $totalCards = 0;
        $mainDeckSize = 0;
        $sideboardSize = 0;

        foreach ($deckCards as $deckCard) {
            $quantity = $deckCard->getQuantity();
            $totalCards += $quantity;

            if ($deckCard->getIsSideboard()) {
                $sideboardSize += $quantity;
            } else {
                $mainDeckSize += $quantity;
            }
        }

        $this->deckRepository->update([
            [
                'id' => $deckId,
                'totalCards' => $totalCards,
                'mainDeckSize' => $mainDeckSize,
                'sideboardSize' => $sideboardSize,
                'updatedAt' => new \DateTime(),
            ]
        ], $context);
    }
}
