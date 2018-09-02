<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Library\Entity\EntityCacheIndex;
use MyHammer\Library\Entity\Schema\DateTimeColumn;
use MyHammer\Library\Entity\Schema\IntColumn;
use MyHammer\Library\Entity\Schema\TableSchema;
use MyHammer\Library\Entity\Schema\VarcharColumn;

class UserEntity extends EntityModel
{

    const INDEX_EMAIL = 'email';

    protected static function getTableSchemaDefinition(): TableSchema
    {
        return new TableSchema(
            'user',
            IntColumn::create('id')->primary()->autoincrement(),
            VarcharColumn::create('email')->allowNull(false)->inUniqueIndex(self::INDEX_EMAIL),
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
            self::INDEX_CATEGORY => new EntityCacheIndex(true, ['email'], 1000)
        ];
    }

    public function setId(int $id) : self
    {
        return $this->setField('id', $id);
    }

    public function getName(): string
    {
        return $this->getField('email');
    }

    public function setName(string $title): self
    {
        $this->setField('email', $title);

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
