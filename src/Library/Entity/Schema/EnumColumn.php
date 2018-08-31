<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class EnumColumn extends ColumnSchema
{
    private $values;
    private $dynamic;

    public function __construct(string $name, array $values)
    {
        parent::__construct($name);
        $this->values = $values;
        $this->dynamic = false;
    }

    public static function create(string $name, array $values): self
    {
        return new EnumColumn($name, $values);
    }

    public function default(string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function convertUserValue($value)
    {
        if ($value === '') {
            return null;
        }
        return $value;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        if (!$this->allowNull) {
            $assert->notNull('cannot be empty')->notEq('', 'cannot be empty');
        }
        if ($value !== null) {
            $assert->inArray($this->values);
        }
    }

    public function dynamic(): self
    {
        $this->dynamic = true;
        return $this;
    }

    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    public function getColumnDefinitionSql(): string
    {
        $finalValues = [];
        foreach ($this->values as $value) {
            $finalValues[] = "'" . addslashes($value) . "'";
        }
        $sql = "`{$this->getName()}` enum(" . implode(',', $finalValues) . ")";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        if (!$this->allowNull && $this->default !== null) {
            $sql .= " DEFAULT '{$this->default}'";
        } elseif ($this->allowNull) {
            $sql .= " DEFAULT NULL";
        }
        return $sql;
    }

    public function getValues() : ?array
    {
        return $this->values;
    }
}
