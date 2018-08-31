<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;
use MyHammer\Library\Entity\Entity;

abstract class BaseReferenceIntColumn extends ColumnSchema
{
    protected $default = null;
    private $withoutDefault = false;
    private $parentEntityClass;

    protected function __construct(string $name, string $parentEntityClass)
    {
        parent::__construct($name);
        $this->parentEntityClass = $parentEntityClass;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        if (!$this->allowNull || $value !== null) {
            $assert->integerish();
            $assert->greaterThan(0);

            /**
             * @todo @shumanski Uncomment this when site is on production
             */
            //            $assert->satisfy(function() use ($value) {
            //                try {
            //                    $this->parentEntityClass::getById($value);
            //                } catch (EntityNotFoundException $e) {
            //                    return false;
            //                }
            //                return true;
            //            }, 'Reference entity does not exists');
        }
    }

    public function convertUserValue($value)
    {
        if ($this->allowNull && !$value) {
            return null;
        }
        return $value;
    }

    public function default(int $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function forceDefaultNull(): self
    {
        $this->default = null;
        $this->allowNull = true;
        return $this;
    }

    public function withoutDefault(): self
    {
        $this->withoutDefault = true;
        return $this;
    }

    public function getParentEntityClass(): string
    {
        return $this->parentEntityClass;
    }

    public function getColumnDefinitionSql(): string
    {
        /**
         * @var Entity $class
         */
        $class = $this->parentEntityClass;
        $entity = $class::newInstance();
        /**
         * @var IntColumn $column
         */
        $tableSchema = $entity::getTableSchema();
        $column = clone $tableSchema->getColumns()['id'];
        $column->autoincrement(false);
        $column->setName($this->getName());

        if ($this->allowNull) {
            $column->forceDefaultNull();
        }

        if ($this->withoutDefault) {
            $column->withoutDefault();
        } elseif ($this->default !== null) {
            $column->default($this->default);
        }

        $column->setIndexes($this->indexes);
        return $column->getColumnDefinitionSql();
    }
}
