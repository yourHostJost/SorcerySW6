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

    // Sorcery-specific game mechanics
    /**
     * @var int|null
     */
    protected $cost;

    /**
     * @var int|null
     */
    protected $attack;

    /**
     * @var int|null
     */
    protected $defence;

    /**
     * @var int|null
     */
    protected $life;

    /**
     * @var array|null
     */
    protected $thresholds;

    /**
     * @var string|null
     */
    protected $elements;

    /**
     * @var string|null
     */
    protected $subTypes;

    // Set and variant information
    /**
     * @var \DateTimeInterface|null
     */
    protected $releaseDate;

    /**
     * @var string|null
     */
    protected $variantSlug;

    /**
     * @var string|null
     */
    protected $finish;

    /**
     * @var string|null
     */
    protected $product;

    /**
     * @var string|null
     */
    protected $artist;

    /**
     * @var string|null
     */
    protected $flavorText;

    /**
     * @var string|null
     */
    protected $typeText;

    // Shop integration
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

    // API integration
    /**
     * @var string|null
     */
    protected $apiSource;

    /**
     * @var string|null
     */
    protected $externalId;

    /**
     * @var \DateTimeInterface|null
     */
    protected $lastApiUpdate;

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

    // Sorcery-specific getters and setters
    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(?int $cost): void
    {
        $this->cost = $cost;
    }

    public function getAttack(): ?int
    {
        return $this->attack;
    }

    public function setAttack(?int $attack): void
    {
        $this->attack = $attack;
    }

    public function getDefence(): ?int
    {
        return $this->defence;
    }

    public function setDefence(?int $defence): void
    {
        $this->defence = $defence;
    }

    public function getLife(): ?int
    {
        return $this->life;
    }

    public function setLife(?int $life): void
    {
        $this->life = $life;
    }

    public function getThresholds(): ?array
    {
        return $this->thresholds;
    }

    public function setThresholds(?array $thresholds): void
    {
        $this->thresholds = $thresholds;
    }

    public function getElements(): ?string
    {
        return $this->elements;
    }

    public function setElements(?string $elements): void
    {
        $this->elements = $elements;
    }

    public function getSubTypes(): ?string
    {
        return $this->subTypes;
    }

    public function setSubTypes(?string $subTypes): void
    {
        $this->subTypes = $subTypes;
    }

    // Set and variant getters and setters
    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    public function getVariantSlug(): ?string
    {
        return $this->variantSlug;
    }

    public function setVariantSlug(?string $variantSlug): void
    {
        $this->variantSlug = $variantSlug;
    }

    public function getFinish(): ?string
    {
        return $this->finish;
    }

    public function setFinish(?string $finish): void
    {
        $this->finish = $finish;
    }

    public function getProduct(): ?string
    {
        return $this->product;
    }

    public function setProduct(?string $product): void
    {
        $this->product = $product;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist): void
    {
        $this->artist = $artist;
    }

    public function getFlavorText(): ?string
    {
        return $this->flavorText;
    }

    public function setFlavorText(?string $flavorText): void
    {
        $this->flavorText = $flavorText;
    }

    public function getTypeText(): ?string
    {
        return $this->typeText;
    }

    public function setTypeText(?string $typeText): void
    {
        $this->typeText = $typeText;
    }

    // API integration getters and setters
    public function getApiSource(): ?string
    {
        return $this->apiSource;
    }

    public function setApiSource(?string $apiSource): void
    {
        $this->apiSource = $apiSource;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getLastApiUpdate(): ?\DateTimeInterface
    {
        return $this->lastApiUpdate;
    }

    public function setLastApiUpdate(?\DateTimeInterface $lastApiUpdate): void
    {
        $this->lastApiUpdate = $lastApiUpdate;
    }
}
