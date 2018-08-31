<?php
namespace MyHammer\Library\Entity\Schema;

use Assert\LazyAssertion;

abstract class ColumnSchema
{
    protected $default;

    private $name;
    protected $autoincrement;
    protected $allowNull = true;
    protected $indexes = [];

    protected function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param bool $allowNull
     * @return $this
     */
    public function allowNull($allowNull = true): self
    {
        $this->allowNull = $allowNull;
        return $this;
    }

    public function isNullAllowed(): bool
    {
        return $this->allowNull;
    }

    public function validate(LazyAssertion $assert, $value)
    {
        $assert->that($value, $this->getName());
    }

    public function convertUserValue($value)
    {
        return $value;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setIndexes(array $indexes)
    {
        $this->indexes = $indexes;
    }

    public function autoincrement($enabled = true): self
    {
        $this->autoincrement = $enabled;
        return $this;
    }

    public function primary(int $position = 0): self
    {
        $this->addToIndex('PRIMARY', $position, true);
        return $this;
    }

    public function inIndex(string $indexName, int $position = 1): self
    {
        $this->addToIndex($indexName, $position, false);
        return $this;
    }

    public function inUniqueIndex(string $indexName, int $position = 1): self
    {
        $this->addToIndex($indexName, $position, true);
        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    abstract public function getColumnDefinitionSql(): string;

    protected function addToIndex(string $indexName, int $position, bool $unique, int $size = null)
    {
        if (!isset($this->indexes[$indexName])) {
            $this->indexes[$indexName] = [];
        }
        $this->indexes[$indexName][$unique ? 'UNIQUE KEY' : 'KEY'][$position] = [$this->getName(), $size];
    }
}
