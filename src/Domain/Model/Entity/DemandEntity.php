<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Library\Entity\EntityCacheIndex;
use MyHammer\Library\Entity\Schema\DateTimeColumn;
use MyHammer\Library\Entity\Schema\EnumColumn;
use MyHammer\Library\Entity\Schema\IntColumn;
use MyHammer\Library\Entity\Schema\ReferenceJsonColumn;
use MyHammer\Library\Entity\Schema\TableSchema;
use MyHammer\Library\Entity\Schema\TextColumn;
use MyHammer\Library\Entity\Schema\VarcharColumn;

class DemandEntity extends EntityModel
{

    const INDEX_CATEGORY = 'category';

    const EXECUTE_TIMES = [self::IMMEDIATELY, self::UP_THREE_DAYS, self::UP_WEEK];
    const IMMEDIATELY = 'immediately';
    const UP_THREE_DAYS = 'three_days';
    const UP_WEEK = 'week';

    protected static function getTableSchemaDefinition(): TableSchema
    {
        return new TableSchema(
            'demands',
            IntColumn::create('id')->primary()->autoincrement(),
            VarcharColumn::create('title', 50)->allowNull(false),
            ReferenceJsonColumn::create('category_id', CategoryEntity::class),
            ReferenceJsonColumn::create('user_id', UserEntity::class),
            IntColumn::create('zipcode', IntColumn::MAX_SIZE_65_THOUSAND)->allowNull(false),
            VarcharColumn::create('city', 10)->allowNull(false),
            EnumColumn::create('execute_time', self::EXECUTE_TIMES)->allowNull(false),
            TextColumn::create('description')->allowNull(false),
            DateTimeColumn::create('created_at')->allowNull(false),
            DateTimeColumn::create('updated_at')->allowNull(true)
        );
    }

    public static function getCacheConnectorCode(): ?string
    {
        return self::MY_HAMMER_LOCAL;
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

    public function setUserId(?int $userId): self
    {
        $this->setField('user_id', $userId);

        return $this;
    }

    public function getUser(): ?UserEntity
    {
        return $this->getOneToOneEntity('user_id');
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

    public function getExecutionTime(): string
    {
        return $this->getField('execute_time');
    }

    public function setExecuteTime(string $executeTime): self
    {
        $this->setField('execute_time', $executeTime);

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

    public function getCreatedAt() : \DateTime
    {
        return $this->mapToDateTime('created_at');
    }

    public function setCreatedAt(\DateTime $createdAt) : self
    {
        return $this->mapFromDateTime('created_at', $createdAt);
    }

    public function getUpdatedAt() : \DateTime
    {
        return $this->mapToDateTime('updated_at');
    }

    public function setUpdatedAt(\DateTime $createdAt) : self
    {
        return $this->mapFromDateTime('updated_at', $createdAt);
    }
}
