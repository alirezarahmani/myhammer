<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class SetColumn extends ColumnSchema
{

    private $values;

    public function __construct(string $name, array $values)
    {
        parent::__construct($name);
        $this->values = $values;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull();
    }

    public static function create(string $name, array $values): self
    {
        return new SetColumn($name, $values);
    }

    public function default(array $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function getColumnDefinitionSql(): string
    {
        $finalValues = [];
        foreach ($this->values as $value) {
            $finalValues[] = "'$value'";
        }
        $sql = "`{$this->getName()}` set(" . implode(',', $finalValues) . ")";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        if (!$this->allowNull && $this->default !== null) {
            $default = implode(',', $this->default);
            $sql .= " DEFAULT '$default'";
        }
        return $sql;
    }
}
