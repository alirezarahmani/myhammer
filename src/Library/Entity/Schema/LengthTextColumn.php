<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

abstract class LengthTextColumn extends ColumnSchema
{

    protected $length;

    public function __construct(string $name, int $length = 255)
    {
        parent::__construct($name);
        $this->length = $length;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull('is required')->notEq('', '`'.$this->getName().'` is required');
        $value !== null && $assert->string()->maxLength($this->length);
    }

    public function default(?string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` {$this->getColumnDefinition()}({$this->length})";
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

    abstract protected function getColumnDefinition(): string;
}
