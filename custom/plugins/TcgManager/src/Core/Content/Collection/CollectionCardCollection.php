<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Collection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(CollectionCardEntity $entity)
 * @method void                         set(string $key, CollectionCardEntity $entity)
 * @method CollectionCardEntity[]       getIterator()
 * @method CollectionCardEntity[]       getElements()
 * @method CollectionCardEntity|null    get(string $key)
 * @method CollectionCardEntity|null    first()
 * @method CollectionCardEntity|null    last()
 */
class CollectionCardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CollectionCardEntity::class;
    }

    /**
     * Get collection cards by collection ID
     */
    public function getByCollectionId(string $collectionId): CollectionCardCollection
    {
        return $this->filter(function (CollectionCardEntity $collectionCard) use ($collectionId) {
            return $collectionCard->getCollectionId() === $collectionId;
        });
    }

    /**
     * Get collection cards by card ID
     */
    public function getByCardId(string $cardId): CollectionCardCollection
    {
        return $this->filter(function (CollectionCardEntity $collectionCard) use ($cardId) {
            return $collectionCard->getCardId() === $cardId;
        });
    }

    /**
     * Get total quantity of a specific card
     */
    public function getTotalQuantityForCard(string $cardId): int
    {
        $total = 0;
        foreach ($this->getByCardId($cardId) as $collectionCard) {
            $total += $collectionCard->getQuantity();
        }
        return $total;
    }

    /**
     * Get collection cards by condition
     */
    public function getByCondition(string $condition): CollectionCardCollection
    {
        return $this->filter(function (CollectionCardEntity $collectionCard) use ($condition) {
            return $collectionCard->getCondition() === $condition;
        });
    }

    /**
     * Get foil cards
     */
    public function getFoilCards(): CollectionCardCollection
    {
        return $this->filter(function (CollectionCardEntity $collectionCard) {
            return $collectionCard->getFoilType() !== null && $collectionCard->getFoilType() !== 'normal';
        });
    }
}
