<?php declare(strict_types=1);

namespace TcgManager\Core\Content\DeckCard;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(DeckCardEntity $entity)
 * @method void                    set(string $key, DeckCardEntity $entity)
 * @method DeckCardEntity[]        getIterator()
 * @method DeckCardEntity[]        getElements()
 * @method DeckCardEntity|null     get(string $key)
 * @method DeckCardEntity|null     first()
 * @method DeckCardEntity|null     last()
 */
class DeckCardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return DeckCardEntity::class;
    }

    /**
     * Get deck cards by deck ID
     */
    public function getByDeckId(string $deckId): DeckCardCollection
    {
        return $this->filter(function (DeckCardEntity $deckCard) use ($deckId) {
            return $deckCard->getDeckId() === $deckId;
        });
    }

    /**
     * Get deck cards by card ID
     */
    public function getByCardId(string $cardId): DeckCardCollection
    {
        return $this->filter(function (DeckCardEntity $deckCard) use ($cardId) {
            return $deckCard->getCardId() === $cardId;
        });
    }

    /**
     * Get main deck cards (not sideboard)
     */
    public function getMainDeckCards(): DeckCardCollection
    {
        return $this->filter(function (DeckCardEntity $deckCard) {
            return !$deckCard->getIsSideboard();
        });
    }

    /**
     * Get sideboard cards
     */
    public function getSideboardCards(): DeckCardCollection
    {
        return $this->filter(function (DeckCardEntity $deckCard) {
            return $deckCard->getIsSideboard();
        });
    }

    /**
     * Get cards by category
     */
    public function getByCategory(string $category): DeckCardCollection
    {
        return $this->filter(function (DeckCardEntity $deckCard) use ($category) {
            return $deckCard->getCategory() === $category;
        });
    }

    /**
     * Get total quantity of cards in main deck
     */
    public function getMainDeckSize(): int
    {
        $total = 0;
        foreach ($this->getMainDeckCards() as $deckCard) {
            $total += $deckCard->getQuantity();
        }
        return $total;
    }

    /**
     * Get total quantity of cards in sideboard
     */
    public function getSideboardSize(): int
    {
        $total = 0;
        foreach ($this->getSideboardCards() as $deckCard) {
            $total += $deckCard->getQuantity();
        }
        return $total;
    }

    /**
     * Get total quantity of all cards
     */
    public function getTotalSize(): int
    {
        $total = 0;
        foreach ($this as $deckCard) {
            $total += $deckCard->getQuantity();
        }
        return $total;
    }
}
