<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Deck;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use TcgManager\Core\Content\DeckCard\DeckCardCollection;

class DeckEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $format;

    /**
     * @var string|null
     */
    protected $archetype;

    /**
     * @var string|null
     */
    protected $colors;

    /**
     * @var bool
     */
    protected $isPublic;

    /**
     * @var bool
     */
    protected $isComplete;

    /**
     * @var bool
     */
    protected $isFeatured;

    /**
     * @var int|null
     */
    protected $totalCards;

    /**
     * @var int|null
     */
    protected $mainDeckSize;

    /**
     * @var int|null
     */
    protected $sideboardSize;

    /**
     * @var string|null
     */
    protected $sourceUrl;

    /**
     * @var string|null
     */
    protected $sourceType;

    /**
     * @var string|null
     */
    protected $externalId;

    /**
     * @var array|null
     */
    protected $tags;

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
     * @var CustomerEntity|null
     */
    protected $customer;

    /**
     * @var DeckCardCollection|null
     */
    protected $deckCards;

    // Getters and Setters
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    public function getArchetype(): ?string
    {
        return $this->archetype;
    }

    public function setArchetype(?string $archetype): void
    {
        $this->archetype = $archetype;
    }

    public function getColors(): ?string
    {
        return $this->colors;
    }

    public function setColors(?string $colors): void
    {
        $this->colors = $colors;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function getIsComplete(): bool
    {
        return $this->isComplete;
    }

    public function setIsComplete(bool $isComplete): void
    {
        $this->isComplete = $isComplete;
    }

    public function getIsFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): void
    {
        $this->isFeatured = $isFeatured;
    }

    public function getTotalCards(): ?int
    {
        return $this->totalCards;
    }

    public function setTotalCards(?int $totalCards): void
    {
        $this->totalCards = $totalCards;
    }

    public function getMainDeckSize(): ?int
    {
        return $this->mainDeckSize;
    }

    public function setMainDeckSize(?int $mainDeckSize): void
    {
        $this->mainDeckSize = $mainDeckSize;
    }

    public function getSideboardSize(): ?int
    {
        return $this->sideboardSize;
    }

    public function setSideboardSize(?int $sideboardSize): void
    {
        $this->sideboardSize = $sideboardSize;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): void
    {
        $this->sourceUrl = $sourceUrl;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
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

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
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
