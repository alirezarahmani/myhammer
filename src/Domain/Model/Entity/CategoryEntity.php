<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Library\Entity\EntityCacheIndex;
use MyHammer\Library\Entity\Schema\TableSchema;

class CategoryEntity extends EntityModel
{

    const INDEX_CATEGORY = 'category';

    protected static function getTableSchemaDefinition(): TableSchema
    {
        return new TableSchema(
            'banners_categories',
            IntColumn::create('id')->primary()->autoincrement(),
            ReferenceIntColumn::create('banner_id', BannerEntity::class)
                ->allowNull(false),
            ReferenceIntColumn::create('category_id', CategoryEntity::class)
                ->allowNull(false)
        );
    }

    public static function getCacheConnectorCode(): ?string
    {
        return 'myHammer:cache:local';
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

    public function getBannerId() : int
    {
        return $this->getField('banner_id');
    }

    public function setBannerId(int $bannerId) : self
    {
        return $this->setField('banner_id', $bannerId);
    }

    public function getCategoryId() : int
    {
        return $this->getField('category_id');
    }

    public function setCategoryId(int $categoryId) : self
    {
        return $this->setField('category_id', $categoryId);
    }

    public function getCategory() : CategoryEntity
    {
        return $this->getOneToOneEntity('category_id');
    }
}
