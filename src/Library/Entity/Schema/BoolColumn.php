<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class BoolColumn extends ColumnSchema
{
    protected $default = 0;
    protected $allowNull = false;

    public static function create(string $name): self
    {
        return new BoolColumn($name);
    }

    public function default(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull('cannot be empty')->notSame('', 'cannot be empty');
        if ($value !== null) {
            $assert->boolean();
        }
    }

    public function convertUserValue($value)
    {
        if (is_bool($value) || is_numeric($value)) {
            return (bool) $value;
        }
        if ($value === 'yes' || $value === 'true') {
            return true;
        } elseif ($value === 'no' || $value === 'false') {
            return false;
        }
        return $value;
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` tinyint(1)";

        if (!$this->allowNull) {
            $sql .= " NOT NULL DEFAULT '" . ($this->default ? 1 : 0) . "'";
        } else {
            $sql .= " DEFAULT NULL";
        }

        return $sql;
    }
}
