<?php declare(strict_types=1);

namespace TcgManager\Core\Content\Collection;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CollectionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'tcg_collection';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CollectionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CollectionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            
            // Foreign key to customer
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            
            // Collection information
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new LongTextField('description', 'description')),
            (new BoolField('is_public', 'isPublic')),
            (new BoolField('is_default', 'isDefault')),
            
            // Metadata
            (new DateTimeField('created_at', 'createdAt')),
            (new DateTimeField('updated_at', 'updatedAt')),
            
            // Associations
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            new OneToManyAssociationField('collectionCards', CollectionCardDefinition::class, 'collection_id'),
        ]);
    }
}
