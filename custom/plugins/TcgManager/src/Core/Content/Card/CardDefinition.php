<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Card;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use TcgManager\Core\Content\Collection\CollectionCardDefinition;
use TcgManager\Core\Content\DeckCard\DeckCardDefinition;

class CardDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'tcg_card';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CardEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CardCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            // Basic card information
            (new StringField('title', 'title'))->addFlags(new Required()),
            (new StringField('edition', 'edition'))->addFlags(new Required()),
            (new StringField('rarity', 'rarity')),
            (new StringField('card_type', 'cardType')),
            (new LongTextField('description', 'description')),

            // Sorcery-specific game mechanics
            (new IntField('cost', 'cost')),
            (new IntField('attack', 'attack')),
            (new IntField('defence', 'defence')),
            (new IntField('life', 'life')),
            (new JsonField('thresholds', 'thresholds')),
            (new StringField('elements', 'elements')),
            (new StringField('sub_types', 'subTypes')),

            // Set and variant information
            (new DateTimeField('release_date', 'releaseDate')),
            (new StringField('variant_slug', 'variantSlug')),
            (new StringField('finish', 'finish')),
            (new StringField('product', 'product')),
            (new StringField('artist', 'artist')),
            (new LongTextField('flavor_text', 'flavorText')),
            (new StringField('type_text', 'typeText')),

            // Legacy fields (for backward compatibility)
            (new IntField('threshold_cost', 'thresholdCost')),
            (new StringField('mana_cost', 'manaCost')),
            (new StringField('set_code', 'setCode')),
            (new StringField('card_number', 'cardNumber')),

            // Shop integration
            (new StringField('image_url', 'imageUrl')),
            (new FloatField('market_price', 'marketPrice')),
            (new IntField('stock_quantity', 'stockQuantity')),

            // API integration
            (new StringField('api_source', 'apiSource')),
            (new StringField('external_id', 'externalId')),
            (new DateTimeField('last_api_update', 'lastApiUpdate')),

            // Metadata and timestamps
            (new JsonField('metadata', 'metadata')),
            (new DateTimeField('created_at', 'createdAt')),
            (new DateTimeField('updated_at', 'updatedAt')),

            // Associations
            new OneToManyAssociationField('collectionCards', CollectionCardDefinition::class, 'card_id'),
            new OneToManyAssociationField('deckCards', DeckCardDefinition::class, 'card_id'),
        ]);
    }
}
