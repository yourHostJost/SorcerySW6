<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Deck;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(DeckEntity $entity)
 * @method void               set(string $key, DeckEntity $entity)
 * @method DeckEntity[]       getIterator()
 * @method DeckEntity[]       getElements()
 * @method DeckEntity|null    get(string $key)
 * @method DeckEntity|null    first()
 * @method DeckEntity|null    last()
 */
class DeckCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DeckEntity::class;
    }

    /**
     * Get decks by customer ID
     */
    public function getByCustomerId(string $customerId): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) use ($customerId) {
            return $deck->getCustomerId() === $customerId;
        });
    }

    /**
     * Get public decks
     */
    public function getPublicDecks(): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) {
            return $deck->getIsPublic();
        });
    }

    /**
     * Get featured decks
     */
    public function getFeaturedDecks(): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) {
            return $deck->getIsFeatured();
        });
    }

    /**
     * Get complete decks
     */
    public function getCompleteDecks(): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) {
            return $deck->getIsComplete();
        });
    }

    /**
     * Get decks by format
     */
    public function getByFormat(string $format): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) use ($format) {
            return $deck->getFormat() === $format;
        });
    }

    /**
     * Get decks by archetype
     */
    public function getByArchetype(string $archetype): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) use ($archetype) {
            return $deck->getArchetype() === $archetype;
        });
    }

    /**
     * Get decks by colors
     */
    public function getByColors(string $colors): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) use ($colors) {
            return $deck->getColors() === $colors;
        });
    }

    /**
     * Get decks from external sources
     */
    public function getExternalDecks(): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) {
            return $deck->getSourceType() === 'external';
        });
    }

    /**
     * Get user-created decks
     */
    public function getUserDecks(): DeckCollection
    {
        return $this->filter(function (DeckEntity $deck) {
            return $deck->getSourceType() === 'user';
        });
    }
}
