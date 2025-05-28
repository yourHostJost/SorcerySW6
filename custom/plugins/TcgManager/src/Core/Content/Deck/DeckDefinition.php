<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Deck;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use TcgManager\Core\Content\DeckCard\DeckCardDefinition;

class DeckDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'tcg_deck';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return DeckEntity::class;
    }

    public function getCollectionClass(): string
    {
        return DeckCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            
            // Foreign key to customer
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            
            // Deck information
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new LongTextField('description', 'description')),
            (new StringField('format', 'format')), // Standard, Modern, Legacy, etc.
            (new StringField('archetype', 'archetype')), // Control, Aggro, Combo, etc.
            (new StringField('colors', 'colors')), // W, U, B, R, G combinations
            
            // Deck status
            (new BoolField('is_public', 'isPublic')),
            (new BoolField('is_complete', 'isComplete')),
            (new BoolField('is_featured', 'isFeatured')),
            (new IntField('total_cards', 'totalCards')),
            (new IntField('main_deck_size', 'mainDeckSize')),
            (new IntField('sideboard_size', 'sideboardSize')),
            
            // External source information
            (new StringField('source_url', 'sourceUrl')),
            (new StringField('source_type', 'sourceType')), // user, external, import
            (new StringField('external_id', 'externalId')),
            
            // Metadata
            (new JsonField('tags', 'tags')),
            (new JsonField('metadata', 'metadata')),
            (new DateTimeField('created_at', 'createdAt')),
            (new DateTimeField('updated_at', 'updatedAt')),
            
            // Associations
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            new OneToManyAssociationField('deckCards', DeckCardDefinition::class, 'deck_id'),
        ]);
    }
}
