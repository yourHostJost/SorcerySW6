<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Collection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use TcgManager\Core\Content\Card\CardDefinition;

class CollectionCardDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'tcg_collection_card';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CollectionCardEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CollectionCardCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            
            // Foreign keys
            (new FkField('collection_id', 'collectionId', CollectionDefinition::class))->addFlags(new Required()),
            (new FkField('card_id', 'cardId', CardDefinition::class))->addFlags(new Required()),
            
            // Card details in collection
            (new IntField('quantity', 'quantity'))->addFlags(new Required()),
            (new StringField('condition', 'condition')), // mint, near mint, played, etc.
            (new StringField('language', 'language')),
            (new StringField('foil_type', 'foilType')), // normal, foil, etched, etc.
            
            // Metadata
            (new DateTimeField('added_at', 'addedAt')),
            (new DateTimeField('updated_at', 'updatedAt')),
            
            // Associations
            new ManyToOneAssociationField('collection', 'collection_id', CollectionDefinition::class, 'id', false),
            new ManyToOneAssociationField('card', 'card_id', CardDefinition::class, 'id', false),
        ]);
    }
}
