<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class IntColumn extends ColumnSchema
{
    const MAX_SIZE_255 = 'TINYINT';
    const MAX_SIZE_65_THOUSAND = 'SMALLINT';
    const MAX_SIZE_16_MILLIONS = 'MEDIUMINT';
    const MAX_SIZE_4_BILLIONS = 'INT';
    const MAX_SIZE_MAX = 'BIGINT';

    const LENGTH = [
        self::MAX_SIZE_255 => 3,
        self::MAX_SIZE_65_THOUSAND => 5,
        self::MAX_SIZE_16_MILLIONS => 8,
        self::MAX_SIZE_4_BILLIONS => 10,
        self::MAX_SIZE_MAX => 20
    ];

    const SIZE = [
        self::MAX_SIZE_255 => 255,
        self::MAX_SIZE_65_THOUSAND => 65535,
        self::MAX_SIZE_16_MILLIONS => 16777215,
        self::MAX_SIZE_4_BILLIONS => 4294967295,
        self::MAX_SIZE_MAX => 18446744073709551615
    ];

    const LENGTH_SIGNED = [
        self::MAX_SIZE_255 => 4,
        self::MAX_SIZE_65_THOUSAND => 6,
        self::MAX_SIZE_16_MILLIONS => 9,
        self::MAX_SIZE_4_BILLIONS => 11,
        self::MAX_SIZE_MAX => 21
    ];

    const SIZE_SIGNED = [
        self::MAX_SIZE_255 => [-128, 127],
        self::MAX_SIZE_65_THOUSAND => [-32768, 32767],
        self::MAX_SIZE_16_MILLIONS => [-8388608, 8388607],
        self::MAX_SIZE_4_BILLIONS => [-2147483648, 2147483647],
        self::MAX_SIZE_MAX => [-9223372036854775808, 9223372036854775807]
    ];

    protected $default = 0;
    protected $allowNull = false;
    private $maxSize;
    private $withoutDefault = false;
    private $unsigned = true;

    protected function __construct(string $name, string $maxSize = 'INT')
    {
        parent::__construct($name);
        $this->maxSize = $maxSize;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull();
        if ($value !== null) {
            $assert->integerish();
            if ($this->unsigned) {
                $assert->greaterOrEqualThan(0)->max(self::SIZE[$this->maxSize]);
            } else {
                $assert->min(self::SIZE_SIGNED[$this->maxSize][0])->max(self::SIZE_SIGNED[$this->maxSize][1]);
            }
        }
    }

    public static function create(string $name, string $maxSize = 'INT'): self
    {
        return new IntColumn($name, $maxSize);
    }

    public function default(int $default): self
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
        $name = strtolower($this->maxSize);
        $sql = "`{$this->getName()}` $name";

        if ($this->unsigned) {
            $sql .=  "(". self::LENGTH[$this->maxSize] .") unsigned";
        } else {
            $sql .=  "(". self::LENGTH_SIGNED[$this->maxSize] .")";
        }

        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        if ($this->autoincrement) {
            $sql .= ' AUTO_INCREMENT';
        } elseif ($this->withoutDefault) {
            ;
        } elseif (!$this->allowNull || $this->default !== null) {
            $sql .= " DEFAULT '{$this->default}'";
        } elseif ($this->allowNull) {
            $sql .= ' DEFAULT NULL';
        }
        return $sql;
    }
}
