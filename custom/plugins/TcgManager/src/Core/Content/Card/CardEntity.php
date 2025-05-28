<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Card;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use TcgManager\Core\Content\Collection\CollectionCardCollection;
use TcgManager\Core\Content\DeckCard\DeckCardCollection;

class CardEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $edition;

    /**
     * @var int
     */
    protected $thresholdCost;

    /**
     * @var string|null
     */
    protected $manaCost;

    /**
     * @var string|null
     */
    protected $rarity;

    /**
     * @var string|null
     */
    protected $cardType;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $imageUrl;

    /**
     * @var string|null
     */
    protected $setCode;

    /**
     * @var string|null
     */
    protected $cardNumber;

    /**
     * @var float|null
     */
    protected $marketPrice;

    /**
     * @var int|null
     */
    protected $stockQuantity;

    /**
     * @var array|null
     */
    protected $metadata;

    /**
     * @var \DateTimeInterface
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @var CollectionCardCollection|null
     */
    protected $collectionCards;

    /**
     * @var DeckCardCollection|null
     */
    protected $deckCards;

    // Getters and Setters
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getEdition(): string
    {
        return $this->edition;
    }

    public function setEdition(string $edition): void
    {
        $this->edition = $edition;
    }

    public function getThresholdCost(): int
    {
        return $this->thresholdCost;
    }

    public function setThresholdCost(int $thresholdCost): void
    {
        $this->thresholdCost = $thresholdCost;
    }

    public function getManaCost(): ?string
    {
        return $this->manaCost;
    }

    public function setManaCost(?string $manaCost): void
    {
        $this->manaCost = $manaCost;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(?string $rarity): void
    {
        $this->rarity = $rarity;
    }

    public function getCardType(): ?string
    {
        return $this->cardType;
    }

    public function setCardType(?string $cardType): void
    {
        $this->cardType = $cardType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getSetCode(): ?string
    {
        return $this->setCode;
    }

    public function setSetCode(?string $setCode): void
    {
        $this->setCode = $setCode;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(?string $cardNumber): void
    {
        $this->cardNumber = $cardNumber;
    }

    public function getMarketPrice(): ?float
    {
        return $this->marketPrice;
    }

    public function setMarketPrice(?float $marketPrice): void
    {
        $this->marketPrice = $marketPrice;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(?int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCollectionCards(): ?CollectionCardCollection
    {
        return $this->collectionCards;
    }

    public function setCollectionCards(CollectionCardCollection $collectionCards): void
    {
        $this->collectionCards = $collectionCards;
    }

    public function getDeckCards(): ?DeckCardCollection
    {
        return $this->deckCards;
    }

    public function setDeckCards(DeckCardCollection $deckCards): void
    {
        $this->deckCards = $deckCards;
    }
}
