<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class JSONColumn extends ColumnSchema
{

    public static function create(string $name): self
    {
        return new JSONColumn($name);
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` mediumtext";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        return $sql;
    }
}
