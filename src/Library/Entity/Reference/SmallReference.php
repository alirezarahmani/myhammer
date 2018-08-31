<?php
namespace MyHammer\Library\Entity\Reference;

use Digikala\Supernova\Service\Mysql\Expression;

abstract class SmallReference extends ManyReference implements \Countable, \Iterator
{
    private $initialized = false;
    private $keys = [];
    private $iteratorKeys = null;
    private $iteratorKey = 0;
    private $indices = [];
    private $extra = [];
    private $extraCacheUpdateStamp = '';

    public function toArray(): array
    {
        $this->initIfNeeded();
        return $this->localData;
    }

    public function clear()
    {
        parent::clear();
        $this->keys = [];
        $this->iteratorKey = null;
        $this->indices = [];
        $this->extra = [];
        $this->extraCacheUpdateStamp = '';
    }

    public function createRow()
    {
        $row = parent::createRow();
        foreach ($this->getStaticColumns() as $key => $value) {
            $row->setField($key, $value);
        }
        return $row;
    }

    public function filterByIndex(string $indexField, $indexValue)
    {
        $this->initIfNeeded();
        if (!isset($this->indices[$indexField])) {
            throw new \InvalidArgumentException("Index $indexField is not defined for " . get_called_class());
        }
        $ret = [];
        foreach ($this->indices[$indexField][$indexValue] ?? [] as $key => $offset) {
            $ret[$key] = $this->offsetGet($offset);
        }
        return $ret;
    }

    public function offsetExists($offset)
    {
        $this->initIfNeeded();
        return parent::offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        $this->initIfNeeded();
        return parent::offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->initIfNeeded();
        if ($offset === null && $this->getDataIndexColumn() == 'id') {
            $offset = $this->createPrimaryKey();
        }
        $rebuildIndices = false;
        if (!in_array($offset, $this->keys)) {
            $this->keys[] = $offset;
            $rebuildIndices = true;
        }
        $value = parent::offsetSet($offset, $value);
        if ($rebuildIndices) {
            foreach (array_keys($this->getCacheIndices()) as $key) {
                $this->updateIndex($key);
            }
        }
        return $value;
    }

    public function offsetUnset($offset)
    {
        $this->initIfNeeded();
        $rebuildIndices = false;
        if (($index = array_search($offset, $this->keys)) !== false) {
            $rebuildIndices = true;
            unset($this->keys[$index]);
            $this->keys = array_values($this->keys);
        }
        $value = parent::offsetUnset($offset);
        if ($rebuildIndices) {
            foreach (array_keys($this->getCacheIndices()) as $key) {
                $this->updateIndex($key);
            }
        }
        return $value;
    }

    public function hasInIndex(string $indexField, $indexValue, int $rowId): bool
    {
        $this->initIfNeeded();
        return isset($this->indices[$indexField][$indexValue][$rowId]);
    }

    public function updateIndex(string $key)
    {
        if ($indices = $this->getCacheIndices()) {
            if (isset($indices[$key]) && $this->localData) {
                $this->rebuildIndices($indices);
            }
        }
    }

    public function count()
    {
        $this->initIfNeeded();
        return count($this->localData);
    }

    public function keys(): array
    {
        $this->initIfNeeded();
        return array_keys($this->localData);
    }

    public function current()
    {
        $this->initIfNeeded();
        return $this->offsetGet($this->iteratorKeys[$this->iteratorKey]);
    }

    public function next()
    {
        $this->initIfNeeded();
        $this->iteratorKey++;
    }

    public function key()
    {
        $this->initIfNeeded();
        return $this->iteratorKeys[$this->iteratorKey];
    }

    public function valid()
    {
        $this->initIfNeeded();
        return isset($this->iteratorKeys[$this->iteratorKey]);
    }

    public function rewind()
    {
        $this->initIfNeeded();
        $this->iteratorKeys = $this->keys;
        $this->iteratorKey = 0;
    }

    public function getExtraCacheData(): array
    {
        $this->initIfNeeded();
        $this->validateExtraCache();
        return $this->extra;
    }

    public function markAsFlushed(array $dataToSet, array $keysToUnset = [])
    {
        parent::markAsFlushed($dataToSet, $keysToUnset);
        $this->keys = $this->localData ? array_keys($this->localData) : [];
    }

    protected function getStaticColumns(): array
    {
        return [];
    }

    protected function updateCache()
    {
        $cacheKey = $this->getCacheKey();
        if ($cacheIndices = $this->getCacheIndices()) {
            $this->rebuildIndices($cacheIndices);
        }
        $this->validateExtraCache(true);
        $this->getCacheConnector()->set(
            $cacheKey,
            [
                '__data' => $this->cleanNullValues($this->localData),
                '__indices' => $this->indices,
                '__extra' => $this->extra
            ],
            $this->getCacheTtl()
        );
    }

    protected function getCacheIndices(): array
    {
        return [];
    }

    protected function addExtraDataToCache(): array
    {
        return [];
    }

    protected function getExtraSelectCondition(): ?Expression
    {
        return null;
    }

    protected function getOrderBy(): ?string
    {
        return null;
    }

    private function initIfNeeded()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $cacheKey = null;
        if ($cache = $this->getCacheConnector()) {
            $cacheKey = $this->getCacheKey();
            $data = (array) $cache->get($cacheKey);
            if ($data) {
                $this->localData = $data['__data'];
                $this->indices = $data['__indices'];
                $this->extra = $data['__extra'];
                $this->keys = array_keys($this->localData);
                return;
            }
        }
        $this->localData = $this->loadRowsFromDatabase();
        $this->keys = array_keys($this->localData);
        if ($cache) {
            $this->updateCache();
        }
    }

    private function cleanNullValues($rows): array
    {
        foreach ($rows as &$row) {
            foreach ($row as $key => &$value) {
                if ($value === null) {
                    unset($value[$key]);
                }
            }
        }
        return $rows;
    }

    private function loadRowsFromDatabase(): array
    {
        $referenceColumn = $this->getReferenceColumn();
        $indexColumn = $this->getDataIndexColumn();
        $data = [];
        $where = new Expression("$referenceColumn = ?", [$this->getEntity()->getId()]);
        foreach ($this->getStaticColumns() as $column => $value) {
            $where->append("AND $column = ?", [$value]);
        }
        if ($extraWhere = $this->getExtraSelectCondition()) {
            $where->append(' AND ' . $extraWhere->getQueryPart(), $extraWhere->getBind());
        }
        if ($orderBy = $this->getOrderBy()) {
            $where->append(' ORDER BY ' . $orderBy);
        }

        $rows = $this->getEntity()->getDbConnector()->selectRows(
            $this->getTableName(),
            $where
        );
        foreach ($rows as $row) {
            unset($row[$referenceColumn]);
            $data[$row[$indexColumn]] = $row;
        }
        return $data;
    }

    private function validateExtraCache(bool $force = false)
    {
        if ($force || $this->isDirty()) {
            $stamp = md5(print_r($this->getChanges(), true));
            if ($stamp != $this->extraCacheUpdateStamp) {
                $this->extraCacheUpdateStamp = $stamp;
                $this->extra = $this->addExtraDataToCache();
            }
        }
    }

    private function rebuildIndices(array $indices)
    {
        $this->indices = [];
        foreach ($indices as $indexColumnName => $default) {
            $this->indices[$indexColumnName] = [];
            foreach ($this->localData as $key => $value) {
                $this->indices[$indexColumnName][$value[$indexColumnName] ?? $default][] = $key;
            }
        }
    }
}
