<?php declare(strict_types=1);

namespace TcgManager\Core\Content\DeckCard;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use TcgManager\Core\Content\Card\CardDefinition;
use TcgManager\Core\Content\Deck\DeckDefinition;

class DeckCardDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'tcg_deck_card';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return DeckCardEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DeckCardCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            
            // Foreign keys
            (new FkField('deck_id', 'deckId', DeckDefinition::class))->addFlags(new Required()),
            (new FkField('card_id', 'cardId', CardDefinition::class))->addFlags(new Required()),
            
            // Card details in deck
            (new IntField('quantity', 'quantity'))->addFlags(new Required()),
            (new BoolField('is_sideboard', 'isSideboard')),
            (new StringField('category', 'category')), // main, sideboard, commander, etc.
            
            // Metadata
            (new DateTimeField('added_at', 'addedAt')),
            (new DateTimeField('updated_at', 'updatedAt')),
            
            // Associations
            new ManyToOneAssociationField('deck', 'deck_id', DeckDefinition::class, 'id', false),
            new ManyToOneAssociationField('card', 'card_id', CardDefinition::class, 'id', false),
        ]);
    }
}
