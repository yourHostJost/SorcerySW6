<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Collection;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use TcgManager\Core\Content\Card\CardEntity;

class CollectionCardEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $collectionId;

    /**
     * @var string
     */
    protected $cardId;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * @var string|null
     */
    protected $condition;

    /**
     * @var string|null
     */
    protected $language;

    /**
     * @var string|null
     */
    protected $foilType;

    /**
     * @var \DateTimeInterface
     */
    protected $addedAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @var CollectionEntity|null
     */
    protected $collection;

    /**
     * @var CardEntity|null
     */
    protected $card;

    // Getters and Setters
    public function getCollectionId(): string
    {
        return $this->collectionId;
    }

    public function setCollectionId(string $collectionId): void
    {
        $this->collectionId = $collectionId;
    }

    public function getCardId(): string
    {
        return $this->cardId;
    }

    public function setCardId(string $cardId): void
    {
        $this->cardId = $cardId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(?string $condition): void
    {
        $this->condition = $condition;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getFoilType(): ?string
    {
        return $this->foilType;
    }

    public function setFoilType(?string $foilType): void
    {
        $this->foilType = $foilType;
    }

    public function getAddedAt(): \DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeInterface $addedAt): void
    {
        $this->addedAt = $addedAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCollection(): ?CollectionEntity
    {
        return $this->collection;
    }

    public function setCollection(CollectionEntity $collection): void
    {
        $this->collection = $collection;
    }

    public function getCard(): ?CardEntity
    {
        return $this->card;
    }

    public function setCard(CardEntity $card): void
    {
        $this->card = $card;
    }
}
