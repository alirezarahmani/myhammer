<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

class TextColumn extends ColumnSchema
{
    const MAX_SIZE_256B = 'TINYTEXT';
    const MAX_SIZE_64KB = 'TEXT';
    const MAX_SIZE_16MB = 'MEDIUMTEXT';
    const MAX_SIZE_4GB = 'LONGTEXT';

    private $maxSize;

    protected function __construct(string $name, string $maxSize = 'TEXT')
    {
        parent::__construct($name);
        $this->maxSize = $maxSize;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        parent::validate($assert, $value);
        !$this->allowNull && $assert->notNull();
    }

    public static function create(string $name, string $maxSize = 'TEXT'): self
    {
        return new TextColumn($name, $maxSize);
    }

    public function default(): self
    {
        throw new \Exception('No default allowed for TEXT fields.');
    }

    public function getDefault()
    {
        return $this->allowNull ? null : '';
    }

    public function getColumnDefinitionSql(): string
    {
        $name = strtolower($this->maxSize);
        $sql = "`{$this->getName()}` $name";
        if (!$this->allowNull) {
            $sql .= ' NOT NULL';
        }
        return $sql;
    }
}
