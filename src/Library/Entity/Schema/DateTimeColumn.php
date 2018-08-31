<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class DateTimeColumn extends ColumnSchema
{
    public static function create(string $name): self
    {
        return new DateTimeColumn($name);
    }

    public function default(\DateTime $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function convertUserValue($value)
    {
        if (is_string($value)) {
            if (!$value) {
                return null;
            }
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
        $sql = "`{$this->getName()}` datetime";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL ';
            if ($this->default === null) {
                $sql .= "DEFAULT '1970-01-01 00:00:00'";
            } else {
                $sql .= "DEFAULT '{$this->default}'";
            }
        } else {
            $sql .= ' DEFAULT NULL';
        }
        return $sql;
    }
}
