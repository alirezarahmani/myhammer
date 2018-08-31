<?php
namespace MyHammer\Library\Entity\Reference;

use MyHammer\Library\Entity\Exception\ReferenceNotFoundException;
use MyHammer\Library\Entity\Reference;

abstract class ManyReference extends Reference implements \ArrayAccess
{

    protected $localData;
    protected $primaryTempKey = -1;
    /**
     * @var Row[]
     */
    protected $references = [];

    /**
     * @return Row
     */
    public function createRow()
    {
        $class = $this->getRowClass();
        /**
         * @var Row $row
         */
        return new $class($this, self::getTableSchema()->getDefaults($this->getDbConnector()->getConnectionUri()));
    }

    public function setRawField($index, $key, $value)
    {
        if (isset($this->localData[$index])) {
            $this->localData[$index][$key] = $value;
        }
    }

    public function clear()
    {
        parent::clear();
        $this->localData = [];
        $this->references = [];
        $this->primaryTempKey = -1;
    }

    abstract public function updateIndex(string $key);

    abstract public function getReferenceColumn(): string;

    abstract public function getDataIndexColumn(): string;

    abstract public function getRowClass(): string;

    public function isDirty(): bool
    {
        if ($this->isMarkToClear()) {
            return true;
        }
        /**
         * @var Row $row
         */
        foreach ($this->references as $row) {
            if ($row->isDirty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Row[]
     */
    public function getChanges(): array
    {
        $changes = [];

        /**
         * @var Row $row
         */
        foreach ($this->references as $key => $row) {
            if ($row->isDirty()) {
                $changes[$key] = $row;
            }
        }
        return $changes;
    }

    public function getCacheKey(): string
    {
        return 'entity:reference:' . $this->getEntity()->getDbConnector()->getConnectionCode()
            . ':' . $this->getEntity()->getTableName() . ':' . $this->getCodeName()
            . ':{' . $this->getEntity()->getId() . '}';
    }

    public function offsetExists($offset)
    {
        return isset($this->localData[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->localData[$offset])) {
            $indexColumn = $this->getDataIndexColumn();
            throw new ReferenceNotFoundException($this->getEntity(), $this->getCodeName() . " $indexColumn = $offset");
        }
        return $this->getReference($offset);
    }

    public function offsetSet($offset, $value)
    {
        /**
         * @var Row $value
         */
        if ($offset === null) {
            $key = $value->getField($this->getDataIndexColumn());
            if (!$key && $this->getDataIndexColumn() === 'id') {
                $key = $this->createPrimaryKey();
            }
            $value->setField($this->getDataIndexColumn(), $key);
            $this->localData[$key] = $value->getRawData();
            $this->references[$key] = $value;
            $offset = $key;
        } else {
            $value->setField($this->getDataIndexColumn(), $offset);
            $this->localData[$offset] = $value->getRawData();
            $this->references[$offset] = $value;
        }
        $this->updateIndex($offset);
    }

    public function offsetUnset($offset)
    {
        $this[$offset]->remove();
        unset($this->localData[$offset]);
        $this->updateIndex($offset);
    }

    public function markAsFlushed(array $dataToSet, array $keysToUnset = [])
    {
        $this->primaryTempKey = -1;
        foreach ($keysToUnset as $key) {
            unset($this->localData[$key], $this->references[$key]);
        }
        foreach ($dataToSet as $key => $value) {
            $this->localData[$key] = $value;
        }
    }

    protected function getReference($offset)
    {
        if (!isset($this->references[$offset])) {
            $class = $this->getRowClass();
            $object = new $class($this, $this->localData[$offset], true);
            $this->references[$offset] = $object;
        }
        return $this->references[$offset];
    }

    protected function createPrimaryKey(): int
    {
        return $this->primaryTempKey--;
    }
}
