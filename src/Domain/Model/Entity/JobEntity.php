<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Library\Entity\EntityCacheIndex;
use MyHammer\Library\Entity\Schema\DateColumn;
use MyHammer\Library\Entity\Schema\IntColumn;
use MyHammer\Library\Entity\Schema\ReferenceJsonColumn;
use MyHammer\Library\Entity\Schema\TableSchema;
use MyHammer\Library\Entity\Schema\TextColumn;
use MyHammer\Library\Entity\Schema\VarcharColumn;

class JobEntity extends EntityModel
{

    const INDEX_CATEGORY = 'category';

    protected static function getTableSchemaDefinition(): TableSchema
    {
        return new TableSchema(
            'jobs',
            IntColumn::create('id')->primary()->autoincrement(),
            VarcharColumn::create('title', 50)->allowNull(false),
            ReferenceJsonColumn::create('category', CategoryEntity::class),
            IntColumn::create('zipcode', IntColumn::MAX_SIZE_65_THOUSAND)->allowNull(false),
            VarcharColumn::create('city', 10)->allowNull(false),
            DateColumn::create('date')->allowNull(false),
            TextColumn::create('description')->allowNull(false)
        );
    }

    public static function getCacheConnectorCode(): ?string
    {
        return 'myHammer:cache:local';
    }

    public static function getCacheIndices(): array
    {
        return [
            self::INDEX_CATEGORY => new EntityCacheIndex(false, ['category_id'], 1000),
        ];
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->setField('category_id', $categoryId);

        return $this;
    }

    public function getCategory(): ?CategoryEntity
    {
        return $this->getOneToOneEntity('category_id');
    }

    public function getTitle(): string
    {
        return $this->getField('title');
    }

    public function setTitle(string $title): self
    {
        $this->setField('title', $title);

        return $this;
    }

    public function getAddress(): Address
    {
        return (new Address($this->getField('city'), $this->getField('zipcode')));
    }

    public function setAddress(Address $address): self
    {
        $this->setField('city', $address->getCity());
        $this->setField('zipcode', $address->getZipCode());

        return $this;
    }

    public function getDescription(): string
    {
        return $this->getField('description');
    }

    public function setDescription(string $title): self
    {
        $this->setField('description', $title);

        return $this;
    }
}
