<?php
namespace MyHammer\Library\Entity;

use MyHammer\Library\Entity\Schema\BaseReferenceIntColumn;

abstract class Sub
{
    private $entity;
    private $parameters;

    use FieldMapperTrait;

    public function __construct(Entity $parentEntity, array $parameters = [])
    {
        $this->entity = $parentEntity;
        $this->parameters = $parameters;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    protected function getParameter(int $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function setField(string $key, $value, bool $cacheOnlyField = false): self
    {
        $this->entity->setField($key, $value, $cacheOnlyField);
        return $this;
    }

    public function setFields(array $fields): self
    {
        foreach ($fields as $key => $value) {
            $this->setField($key, $value);
        }
        return $this;
    }

    public function getField(string $key, bool $cacheOnlyField = false)
    {
        return $this->entity->getField($key, $cacheOnlyField);
    }

    public function getTranslatedField(string $key): ?string
    {
        return $this->entity->getTranslatedField($key);
    }

    public function getFields(): array
    {
        return $this->entity->getFields();
    }

    /**
     * @param string $entityIdFieldName
     * @return Entity|mixed
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
        $column = $this->entity::getTableSchema()->getColumns()[$entityIdFieldName];
        /**
         * @var Entity $class
         */
        $class = $column->getParentEntityClass();
        return $class::getById($id);
    }
}
