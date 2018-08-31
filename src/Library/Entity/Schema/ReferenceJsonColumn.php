<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class ReferenceJsonColumn extends ColumnSchema
{
    private $parentEntityClass;

    protected function __construct(string $name, string $parentEntityClass)
    {
        parent::__construct($name);
        $this->parentEntityClass = $parentEntityClass;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
    }

    public static function create(string $name, string $parentEntityClass): self
    {
        return new ReferenceJsonColumn($name, $parentEntityClass);
    }

    public function getParentEntityClass(): string
    {
        return $this->parentEntityClass;
    }

    public function getColumnDefinitionSql(): string
    {
        return "`{$this->getName()}` json DEFAULT NULL";
    }

    public function convertUserValue($value)
    {
        if (!$value) {
            return null;
        } elseif (is_array($value)) {
            foreach ($value as &$val) {
                $val = (int) $val;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }
}
