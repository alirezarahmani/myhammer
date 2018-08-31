<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class FloatColumn extends ColumnSchema
{
    protected $default = 0;
    protected $allowNull = false;
    private $unsigned = false;

    public static function create(string $name): self
    {
        return new FloatColumn($name);
    }

    public function default(float $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function unsinged(): self
    {
        $this->unsigned = true;
        return $this;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull();
        if ($value !== null) {
            $this->unsigned && $assert->greaterOrEqualThan(0);
        }
    }

    public function forceDefaultNull(): self
    {
        $this->default = null;
        $this->allowNull = true;
        return $this;
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` float";

        if ($this->unsigned) {
            $sql .= ' unsigned';
        }

        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        if (!$this->allowNull && $this->default !== null) {
            $sql .= " DEFAULT '{$this->default}'";
        } elseif ($this->allowNull) {
            $sql .= ' DEFAULT NULL';
        }

        return $sql;
    }
}
