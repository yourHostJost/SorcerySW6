<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Collection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(CollectionEntity $entity)
 * @method void                    set(string $key, CollectionEntity $entity)
 * @method CollectionEntity[]      getIterator()
 * @method CollectionEntity[]      getElements()
 * @method CollectionEntity|null   get(string $key)
 * @method CollectionEntity|null   first()
 * @method CollectionEntity|null   last()
 */
class CollectionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CollectionEntity::class;
    }

    /**
     * Get collections by customer ID
     */
    public function getByCustomerId(string $customerId): CollectionCollection
    {
        return $this->filter(function (CollectionEntity $collection) use ($customerId) {
            return $collection->getCustomerId() === $customerId;
        });
    }

    /**
     * Get public collections
     */
    public function getPublicCollections(): CollectionCollection
    {
        return $this->filter(function (CollectionEntity $collection) {
            return $collection->getIsPublic();
        });
    }

    /**
     * Get default collection for customer
     */
    public function getDefaultCollection(string $customerId): ?CollectionEntity
    {
        $collections = $this->getByCustomerId($customerId);
        
        foreach ($collections as $collection) {
            if ($collection->getIsDefault()) {
                return $collection;
            }
        }

        return null;
    }
}
