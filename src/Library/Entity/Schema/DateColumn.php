<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class DateColumn extends ColumnSchema
{

    public static function create(string $name): self
    {
        return new DateColumn($name);
    }

    public function default(\DateTime $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function convertUserValue($value)
    {
        if (is_string($value)) {
            return new \DateTime($value);
        }
        return $value;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull('cannot be empty');
        if ($value !== null) {
            $assert->isInstanceOf(\DateTime::class);
        }
    }

    public function getColumnDefinitionSql(): string
    {
        $sql = "`{$this->getName()}` date";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL ';
            if ($this->default === null) {
                $sql .= "DEFAULT '1970-01-01'";
            } else {
                $sql .= "DEFAULT '{$this->default}'";
            }
        } else {
            $sql .= ' DEFAULT NULL';
        }
        return $sql;
    }
}
