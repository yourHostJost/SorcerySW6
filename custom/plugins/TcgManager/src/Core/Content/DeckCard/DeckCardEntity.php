<?php declare(strict_types=1);

namespace TcgManager\Core\Content\DeckCard;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use TcgManager\Core\Content\Card\CardEntity;
use TcgManager\Core\Content\Deck\DeckEntity;

class DeckCardEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $deckId;

    /**
     * @var string
     */
    protected $cardId;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * @var bool
     */
    protected $isSideboard;

    /**
     * @var string|null
     */
    protected $category;

    /**
     * @var \DateTimeInterface
     */
    protected $addedAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @var DeckEntity|null
     */
    protected $deck;

    /**
     * @var CardEntity|null
     */
    protected $card;

    // Getters and Setters
    public function getDeckId(): string
    {
        return $this->deckId;
    }

    public function setDeckId(string $deckId): void
    {
        $this->deckId = $deckId;
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

    public function getIsSideboard(): bool
    {
        return $this->isSideboard;
    }

    public function setIsSideboard(bool $isSideboard): void
    {
        $this->isSideboard = $isSideboard;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
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

    public function getDeck(): ?DeckEntity
    {
        return $this->deck;
    }

    public function setDeck(DeckEntity $deck): void
    {
        $this->deck = $deck;
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
