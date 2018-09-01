<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Library\Entity\EntityCacheIndex;
use MyHammer\Library\Entity\Schema\IntColumn;
use MyHammer\Library\Entity\Schema\TableSchema;
use MyHammer\Library\Entity\Schema\VarcharColumn;

class CategoryEntity extends EntityModel
{

    const INDEX_CATEGORY = 'category';

    protected static function getTableSchemaDefinition(): TableSchema
    {
        return new TableSchema(
            'banners_categories',
            IntColumn::create('id')->primary()->autoincrement(),
            VarcharColumn::create('title')->allowNull(false)->inUniqueIndex()
        );
    }

    public static function getCacheConnectorCode(): ?string
    {
        return self::MY_HAMMER_LOCAL;
    }

    public static function getCacheIndices(): array
    {
        return [
            self::INDEX_CATEGORY => new EntityCacheIndex(false, ['category_id'], 1000)
        ];
    }

    public function setId(int $id) : self
    {
        return $this->setField('id', $id);
    }
}
