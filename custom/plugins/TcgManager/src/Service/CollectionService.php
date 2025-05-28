<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use TcgManager\Core\Content\Collection\CollectionEntity;
use TcgManager\Core\Content\Collection\CollectionCollection;

class CollectionService
{
    private EntityRepository $collectionRepository;
    private EntityRepository $collectionCardRepository;
    private EntityRepository $cardRepository;

    public function __construct(
        EntityRepository $collectionRepository,
        EntityRepository $collectionCardRepository,
        EntityRepository $cardRepository
    ) {
        $this->collectionRepository = $collectionRepository;
        $this->collectionCardRepository = $collectionCardRepository;
        $this->cardRepository = $cardRepository;
    }

    /**
     * Create a new collection for a customer
     */
    public function createCollection(
        string $customerId,
        string $name,
        ?string $description = null,
        bool $isPublic = false,
        bool $isDefault = false,
        Context $context = null
    ): string {
        $context = $context ?? Context::createDefaultContext();
        $collectionId = Uuid::randomHex();

        // If this is set as default, unset other default collections for this customer
        if ($isDefault) {
            $this->unsetDefaultCollections($customerId, $context);
        }

        $collectionData = [
            'id' => $collectionId,
            'customerId' => $customerId,
            'name' => $name,
            'description' => $description,
            'isPublic' => $isPublic,
            'isDefault' => $isDefault,
            'createdAt' => new \DateTime(),
        ];

        $this->collectionRepository->create([$collectionData], $context);

        return $collectionId;
    }

    /**
     * Get collections for a customer
     */
    public function getCustomerCollections(string $customerId, Context $context = null): CollectionCollection
    {
        $context = $context ?? Context::createDefaultContext();
        
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('collectionCards.card');

        $result = $this->collectionRepository->search($criteria, $context);
        
        return $result->getEntities();
    }

    /**
     * Get default collection for a customer
     */
    public function getDefaultCollection(string $customerId, Context $context = null): ?CollectionEntity
    {
        $context = $context ?? Context::createDefaultContext();
        
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            new EqualsFilter('isDefault', true)
        ]));

        $result = $this->collectionRepository->search($criteria, $context);
        
        return $result->first();
    }

    /**
     * Add a card to a collection
     */
    public function addCardToCollection(
        string $collectionId,
        string $cardId,
        int $quantity = 1,
        ?string $condition = null,
        ?string $language = 'en',
        ?string $foilType = 'normal',
        Context $context = null
    ): string {
        $context = $context ?? Context::createDefaultContext();

        // Check if card already exists in collection with same properties
        $existingCard = $this->getCollectionCard($collectionId, $cardId, $condition, $language, $foilType, $context);
        
        if ($existingCard) {
            // Update quantity
            $this->collectionCardRepository->update([
                [
                    'id' => $existingCard['id'],
                    'quantity' => $existingCard['quantity'] + $quantity,
                    'updatedAt' => new \DateTime(),
                ]
            ], $context);
            
            return $existingCard['id'];
        } else {
            // Create new collection card entry
            $collectionCardId = Uuid::randomHex();
            
            $collectionCardData = [
                'id' => $collectionCardId,
                'collectionId' => $collectionId,
                'cardId' => $cardId,
                'quantity' => $quantity,
                'condition' => $condition,
                'language' => $language,
                'foilType' => $foilType,
                'addedAt' => new \DateTime(),
            ];

            $this->collectionCardRepository->create([$collectionCardData], $context);
            
            return $collectionCardId;
        }
    }

    /**
     * Remove a card from a collection
     */
    public function removeCardFromCollection(
        string $collectionId,
        string $cardId,
        int $quantity = 1,
        ?string $condition = null,
        ?string $language = 'en',
        ?string $foilType = 'normal',
        Context $context = null
    ): bool {
        $context = $context ?? Context::createDefaultContext();

        $existingCard = $this->getCollectionCard($collectionId, $cardId, $condition, $language, $foilType, $context);
        
        if (!$existingCard) {
            return false;
        }

        $newQuantity = $existingCard['quantity'] - $quantity;
        
        if ($newQuantity <= 0) {
            // Remove the card completely
            $this->collectionCardRepository->delete([['id' => $existingCard['id']]], $context);
        } else {
            // Update quantity
            $this->collectionCardRepository->update([
                [
                    'id' => $existingCard['id'],
                    'quantity' => $newQuantity,
                    'updatedAt' => new \DateTime(),
                ]
            ], $context);
        }

        return true;
    }

    /**
     * Get a specific collection card
     */
    private function getCollectionCard(
        string $collectionId,
        string $cardId,
        ?string $condition,
        ?string $language,
        ?string $foilType,
        Context $context
    ): ?array {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('collectionId', $collectionId),
            new EqualsFilter('cardId', $cardId),
            new EqualsFilter('condition', $condition),
            new EqualsFilter('language', $language),
            new EqualsFilter('foilType', $foilType),
        ]));

        $result = $this->collectionCardRepository->search($criteria, $context);
        
        return $result->first();
    }

    /**
     * Unset default flag for all collections of a customer
     */
    private function unsetDefaultCollections(string $customerId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            new EqualsFilter('isDefault', true)
        ]));

        $collections = $this->collectionRepository->search($criteria, $context);
        
        $updates = [];
        foreach ($collections as $collection) {
            $updates[] = [
                'id' => $collection->getId(),
                'isDefault' => false,
            ];
        }

        if (!empty($updates)) {
            $this->collectionRepository->update($updates, $context);
        }
    }

    /**
     * Create default collection for new customer
     */
    public function createDefaultCollectionForCustomer(string $customerId, Context $context = null): string
    {
        return $this->createCollection(
            $customerId,
            'Meine Sammlung',
            'Standardsammlung für alle meine Karten',
            false,
            true,
            $context
        );
    }
}
