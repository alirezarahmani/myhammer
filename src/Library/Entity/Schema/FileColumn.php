<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class FileColumn extends ColumnSchema
{

    public static function create(string $name): self
    {
        return new FileColumn($name);
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` mediumtext";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        return $sql;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull('cannot be empty')->notEq('', 'cannot be empty');
    }

    public function convertUserValue($value)
    {
        if (!$value) {
            return $this->allowNull ? null : [];
        }
        return $value;
    }
}
