<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class LongBlobColumn extends ColumnSchema
{

    public static function create(string $name): self
    {
        return new LongBlobColumn($name);
    }

    public function default(string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull();
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` longblob";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        if (!$this->allowNull && $this->default !== null) {
            $sql .= " DEFAULT '{$this->default}'";
        }
        return $sql;
    }
}
