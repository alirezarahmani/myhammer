<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class DecimalColumn extends ColumnSchema
{
    protected $default = 0;
    protected $allowNull = false;
    private $withoutDefault = false;
    private $unsigned = true;
    private $length;
    private $decimals;

    protected function __construct(string $name, int $length, int $decimals)
    {
        parent::__construct($name);
        $this->length = $length;
        $this->decimals = $decimals;
        $this->default = '0';
        if ($decimals) {
            $this->default .= '.' . str_repeat('0', $decimals);
        }
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull();
        if ($value !== null) {
            $this->unsigned && $assert->greaterOrEqualThan(0);
        }
    }

    public static function create(string $name, int $length, int $decimals): self
    {
        return new DecimalColumn($name, $length, $decimals);
    }

    public function default(string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function signed() : self
    {
        $this->unsigned = false;
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

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` decimal({$this->length},{$this->decimals})";

        if ($this->unsigned) {
            $sql .= " unsigned";
        }

        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        if ($this->withoutDefault) {
            ;
        } elseif (!$this->allowNull || $this->default !== null) {
            $default = $this->default;
            if ($this->decimals && strpos($default, '.') === false) {
                $default .= '.' . str_repeat('0', $this->decimals);
            }
            $sql .= " DEFAULT '$default'";
        } elseif ($this->allowNull) {
            $sql .= ' DEFAULT NULL';
        }
        return $sql;
    }
}
