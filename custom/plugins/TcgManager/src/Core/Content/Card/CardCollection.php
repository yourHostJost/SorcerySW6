<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Card;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(CardEntity $entity)
 * @method void               set(string $key, CardEntity $entity)
 * @method CardEntity[]       getIterator()
 * @method CardEntity[]       getElements()
 * @method CardEntity|null    get(string $key)
 * @method CardEntity|null    first()
 * @method CardEntity|null    last()
 */
class CardCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CardEntity::class;
    }

    /**
     * Get cards by edition
     */
    public function getByEdition(string $edition): CardCollection
    {
        return $this->filter(function (CardEntity $card) use ($edition) {
            return $card->getEdition() === $edition;
        });
    }

    /**
     * Get cards by rarity
     */
    public function getByRarity(string $rarity): CardCollection
    {
        return $this->filter(function (CardEntity $card) use ($rarity) {
            return $card->getRarity() === $rarity;
        });
    }

    /**
     * Get cards by type
     */
    public function getByType(string $cardType): CardCollection
    {
        return $this->filter(function (CardEntity $card) use ($cardType) {
            return $card->getCardType() === $cardType;
        });
    }

    /**
     * Get cards with threshold cost in range
     */
    public function getByThresholdCostRange(int $min, int $max): CardCollection
    {
        return $this->filter(function (CardEntity $card) use ($min, $max) {
            $cost = $card->getThresholdCost();
            return $cost >= $min && $cost <= $max;
        });
    }

    /**
     * Get cards that are in stock
     */
    public function getInStock(): CardCollection
    {
        return $this->filter(function (CardEntity $card) {
            return $card->getStockQuantity() !== null && $card->getStockQuantity() > 0;
        });
    }

    /**
     * Sort by threshold cost
     */
    public function sortByThresholdCost(bool $ascending = true): CardCollection
    {
        $this->sort(function (CardEntity $a, CardEntity $b) use ($ascending) {
            $result = $a->getThresholdCost() <=> $b->getThresholdCost();
            return $ascending ? $result : -$result;
        });

        return $this;
    }

    /**
     * Sort by market price
     */
    public function sortByMarketPrice(bool $ascending = true): CardCollection
    {
        $this->sort(function (CardEntity $a, CardEntity $b) use ($ascending) {
            $priceA = $a->getMarketPrice() ?? 0;
            $priceB = $b->getMarketPrice() ?? 0;
            $result = $priceA <=> $priceB;
            return $ascending ? $result : -$result;
        });

        return $this;
    }
}
