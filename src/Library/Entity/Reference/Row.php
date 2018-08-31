<?php
namespace MyHammer\Library\Entity\Reference;

use MyHammer\Library\Entity\Entity;
use MyHammer\Library\Entity\FieldMapperTrait;
use MyHammer\Library\Entity\Reference;
use MyHammer\Library\Entity\Schema\BaseReferenceIntColumn;
use MyHammer\Library\Entity\Schema\ReferenceJsonColumn;
use Digikala\Supernova\Services;

class Row implements \ArrayAccess, \Countable
{
    use Services;
    use FieldMapperTrait;

    private $dbData;
    private $localData;
    private $changes;
    private $reference;
    private $isInDb;
    private $toRemove;
    private $entity;

    public function __construct(ManyReference $reference, array $data = [], bool $isInDb = false)
    {
        $data[$reference->getReferenceColumn()] = $reference->getEntity()->getId();
        $this->dbData = $this->localData = $data;
        $this->entity = $reference->getEntity();
        $this->changes = $isInDb ? [] : $data;
        $this->reference = $reference;
        $this->isInDb = $isInDb;
        $this->toRemove = false;
    }

    public function isDirty(): bool
    {
        return !empty($this->changes) || $this->toRemove;
    }

    public function isInDb(): bool
    {
        return $this->isInDb;
    }

    public function setIsInDb(): self
    {
        $this->isInDb = true;
        $this->changes = [];
        $this->dbData = $this->localData;
        return $this;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function isFieldChanged(string $key): bool
    {
        return isset($this->changes[$key]);
    }

    public function getRawData(): array
    {
        return $this->localData;
    }

    public function offsetExists($offset)
    {
        return isset($this->localData[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->localData[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->localData[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->localData[$offset]);
    }

    public function count()
    {
        return count($this->localData);
    }

    public function isToRemove(): bool
    {
        return $this->toRemove;
    }

    public function remove()
    {
        $this->toRemove = true;
    }

    /**
     * @param string $key
     * @param $value
     * @return self|mixed
     */
    public function setField(string $key, $value): self
    {
        $left = $this->dbData[$key] ?? null;
        $right = $value;
        if ($this->isInDb) {
            if ($left === null && $right !== null) {
                $this->changes[$key] = $value;
            } elseif ($right === null && $left !== null) {
                $this->changes[$key] = $value;
            } elseif ($left != $value) {
                $this->changes[$key] = $value;
            } else {
                unset($this->changes[$key]);
            }
        } else {
            $this->changes[$key] = $value;
        }

        $this->localData[$key] = $value;
        $this->reference->setRawField($this->localData[$this->reference->getDataIndexColumn()] ?? null, $key, $value);
        if (isset($this->changes[$key])) {
            $this->reference->updateIndex($key);
        }
        return $this;
    }

    public function setFields(array $fields): self
    {
        foreach ($fields as $key => $value) {
            $this->setField($key, $value);
        }
        return $this;
    }

    public function getField(string $key)
    {
        return $this->localData[$key] ?? null;
    }

    /**
     * @param string $entityIdFieldName
     * @return Entity|mixed
     * @throws \Digikala\Supernova\Lib\Entity\Exception\EntityNotFoundException
     */
    protected function getOneToOneEntity(string $entityIdFieldName) : ?Entity
    {
        $id = $this->getField($entityIdFieldName);
        if (!$id) {
            return null;
        }
        /**
         * @var BaseReferenceIntColumn $column
         */
        $column = $this->getReference()::getTableSchema()->getColumns()[$entityIdFieldName];
        /**
         * @var Entity $class
         */
        $class = $column->getParentEntityClass();
        return $class::getById($id);
    }

    /**
     * @param string $entityIdFieldName
     * @return Entity[]
     * @throws \Digikala\Supernova\Lib\Entity\Exception\EntityNotFoundException
     * @throws \Exception
     */
    protected function getOneToManyEntities(string $entityIdFieldName) : array
    {
        $ids = $this->getOneToManyEntitiesIDs($entityIdFieldName);
        if (!$ids) {
            return $ids;
        }
        /**
         * @var ReferenceJsonColumn $column
         */
        $column = $this->getReference()::getTableSchema()->getColumns()[$entityIdFieldName];
        /**
         * @var Entity $class
         */
        $class = $column->getParentEntityClass();
        return $class::getByIds($ids);
    }

    /**
     * @param string $entityIdFieldName
     * @return int[]
     */
    protected function getOneToManyEntitiesIDs(string $entityIdFieldName) : array
    {
        $ids = $this->mapToArrayFromJson($entityIdFieldName);
        return $ids ?? [];
    }

    /**
     * @param string $entityIdFieldName
     * @param array  $ids
     * @return Entity|mixed
     */
    protected function setOneToManyEntitiesIDs(string $entityIdFieldName, array $ids) : self
    {
        return $this->mapFromArrayToJson($entityIdFieldName, $ids ? $ids : null);
    }

    function __debugInfo() // phpcs:ignore
    {
        return [
            'Row in reference ' . $this->reference->getCodeName() . ' in entity '
            . get_class($this->entity) .' with ID ' . $this->entity->getId(),
            $this->localData
        ];
    }

    protected function getEntity(): Entity
    {
        return $this->entity;
    }

    protected function getReference(): Reference
    {
        return $this->reference;
    }
}
