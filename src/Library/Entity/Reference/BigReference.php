<?php
namespace MyHammer\Library\Entity\Reference;

use MyHammer\Library\Entity\Entity;
use MyHammer\Library\Entity\EntityCacheIndex;
use MyHammer\Library\Entity\Exception\ReferenceNotFoundException;
use MyHammer\Library\Entity\Schema\BaseReferenceIntColumn;
use MyHammer\Library\Entity\Schema\CachedReferenceIntColumn;
use MyHammer\Library\Entity\SortableEntityCacheIndex;
use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Service\Mysql\Pager;
use MyHammer\Library\Service\TimeService;

abstract class BigReference extends ManyReference implements \Iterator
{

    const ROWS_IN_CACHED_PAGE = 1000;

    private $dbData;
    private $initialized = [];

    public function offsetExists($offset)
    {
        $this->init($offset);
        return parent::offsetExists($offset);
    }

    /**
     * @param mixed $offset
     * @return Row|mixed
     * @throws ReferenceNotFoundException
     */
    public function offsetGet($offset)
    {
        $this->init($offset);
        return parent::offsetGet($offset);
    }

    public function clear()
    {
        parent::clear();
        $this->dbData = [];
        $this->initialized = [];
    }

    public function getPage(Pager $pager, Expression $extraWhere = null, bool $onlyKeys = false): array
    {
        if (!$this->getEntity()->getId()) {
            return [];
        }
        $fieldName = $this->getReferenceColumn();
        $column = static::getTableSchema()->getColumns()[$fieldName];
        if (!($column instanceof BaseReferenceIntColumn)) {
            throw new \InvalidArgumentException("column $fieldName is not referenced");
        }
        $where = new Expression("$fieldName = ?", [$this->getEntity()->getId()]);
        if ($extraWhere) {
            $where->append('AND ' . $extraWhere->getQueryPart(), $extraWhere->getBind());
        }
        if (!$extraWhere && ($column instanceof CachedReferenceIntColumn)) {
            if (!$this->getCacheConnector()) {
                throw new \InvalidArgumentException("cache connector not defined");
            }
            $where->append('ORDER BY ' . $column->getOrderPart());
            $keyPrefix = 'entity:m:refs:' . md5(get_class($this->getEntity()) . $this->getCodeName() . $fieldName)
                . ':' . $this->getEntity()->getId() . ':';
            $keys = [];
            $firstRow = ($pager->getCurrentPage() - 1) * $pager->getRowsOnPage();
            $lastRow = $firstRow + $pager->getRowsOnPage();
            $firstPage = (int) floor($firstRow / $column->getCachePageSize()) + 1;
            $lastPage = (int) ceil($lastRow / $column->getCachePageSize());
            for ($i = $firstPage; $i <= $lastPage; $i++) {
                $keys[] = $keyPrefix . 'page:' . $i;
            }
            $values = $this->getCacheConnector()->gets($keys);
            $missingPages = [];
            foreach ($values as $key => $value) {
                if ($value === null) {
                    $exploded = explode(':', $key);
                    $page = (int) array_pop($exploded);
                    $missingPages[$page] = $key;
                }
            }
            if ($missingPages) {
                foreach ($missingPages as $page => $key) {
                    $subPager = new Pager($page, $column->getCachePageSize());
                    $ids = $this->getDbConnector()->selectColumn(
                        $this->getTableName(),
                        $this->getDataIndexColumn(),
                        $where,
                        $subPager
                    );
                    $ids = implode(',', array_merge([$subPager->getTotalRows()], $ids));
                    $this->getCacheConnector()->set($key, $ids, TimeService::MONTH);
                    $pager->setTotalRows($subPager->getTotalRows());
                    $values[$key] = $ids;
                }
            }
            $ids = [];
            $i = $firstPage;

            foreach ($values as $value) {
                $exploded = explode(',', $value);
                $pager->setTotalRows(array_shift($exploded));
                $firstRowCache = ($i - 1) * $column->getCachePageSize();
                $lastRowCache = $firstRowCache + $column->getCachePageSize();
                $slicedIds = $exploded;
                $sliceStart = 0;
                $sliceLength = $pager->getRowsOnPage();
                if ($i == $firstPage) {
                    $sliceStart = $firstRow - $firstRowCache;
                } elseif ($i == $lastPage) {
                    $sliceLength = $lastRowCache - $firstRow - $sliceStart;
                }
                $ids = array_merge($ids, array_slice($slicedIds, $sliceStart, $sliceLength));
                $i++;
            }
            return $onlyKeys ? $ids : $this->offsetGets($ids);
        }
        $ids = $this->getDbConnector()->selectColumn(
            $this->getTableName(),
            $this->getDataIndexColumn(),
            $where,
            $pager
        );
        return $onlyKeys ? $ids : $this->offsetGets($ids);
    }

    /**
     * @param string $indexCode
     * @param Pager  $pager
     * @param array  ...$value
     * @return $this[]
     */
    final public function getManyIdsByIndex(string $indexCode, Pager $pager, ...$value): array
    {
        return $this->getIdsFromIndex($indexCode, true, $value, false, $pager, null);
    }

    /**
     * @param string $indexCode
     * @param Pager  $pager
     * @param array  $values
     * @param array  $sort
     * @return $this[]
     */
    final public function getManyIdsByIndexWithSort(string $indexCode, Pager $pager, array $values, array $sort): array
    {
        return $this->getIdsFromIndex($indexCode, true, $values, false, $pager, $sort);
    }

    /**
     * @param string $indexCode
     * @param Pager  $pager
     * @param array  ...$value
     * @return $this[]
     */
    final public function getManyByIndex(string $indexCode, Pager $pager, ...$value): array
    {
        return $this->getIdsFromIndex($indexCode, true, $value, true, $pager, null);
    }

    /**
     * @param string $indexCode
     * @param Pager  $pager
     * @param array  $values
     * @param array  $sort
     * @return $this[]
     */
    final public function getManyByIndexWithSort(string $indexCode, Pager $pager, array $values, array $sort): array
    {
        return $this->getIdsFromIndex($indexCode, true, $values, true, $pager, $sort);
    }

    final public function getManyIdsByQuery(Expression $expression, Pager $pager): array
    {
        $referenceColumn = $this->getReferenceColumn();
        $expression->append("AND $referenceColumn = ?". [$this->getEntity()->getId()]);

        return $this::getEntity()->getDbConnector()->selectColumn(
            $this->getTableName(),
            $this->getDataIndexColumn(),
            $expression,
            $pager
        );
    }

    /**
     * @param Expression $expression
     * @param Pager      $pager
     * @return $this[]
     * @throws ReferenceNotFoundException
     */
    final public function getManyByQuery(Expression $expression, Pager $pager): array
    {
        if ($this->getEntity()::getCacheService()) {
            $ids = $this->getManyIdsByQuery($expression, $pager);
            return $ids ? $this->offsetGets($ids) : [];
        }
        $referenceColumn = $this->getReferenceColumn();
        $expression->append("AND $referenceColumn = ?". [$this->getEntity()->getId()]);
        $rows = $this->getEntity()->getDbConnector()->selectRows(
            $this->getTableName(),
            $expression,
            $pager
        );
        $results = [];
        foreach ($rows as $row) {
            $id = $row[$this->getDataIndexColumn()];
            if (!isset($this->initialized[$id])) {
                $this->dbData[$id] = $this->localData[$id] = $row;
                $this->initialized[$id] = 1;
                unset($row[$referenceColumn]);
            }
            $results[$id] = $this->offsetGet($id);
        }
        return $results;
    }

    public function updateIndex(string $key)
    {
        //not needed
    }

    /**
     * @param array $offsets
     * @return array
     * @throws ReferenceNotFoundException
     */
    public function offsetGets(array $offsets)
    {
        $results = [];
        $keys = [];
        $cache = $this->getCacheConnector();
        foreach ($offsets as $offset) {
            if (isset($this->initialized[$offset])) {
                $results[$offset] = $this->getReference($offset);
            } else {
                $results[$offset] = null;
                if ($cache) {
                    $keys[$this->getCacheKey() . ':' . $offset] = $offset;
                } else {
                    $keys[$offset] = $offset;
                }
            }
        }
        if ($keys) {
            if ($cache) {
                $dbIds = [];
                foreach ($cache->gets(array_keys($keys)) as $key => $value) {
                    $id = $keys[$key];
                    if ($value === null) {
                        $dbIds[$id] = $key;
                    } else {
                        $this->dbData[$id] = $this->localData[$id] = $value;
                        $this->initialized[$id] = 1;
                        $results[$keys[$key]] = $this->getReference($id);
                    }
                }
            } else {
                $dbIds = $keys;
            }
            if ($dbIds) {
                $referenceColumn = $this->getReferenceColumn();
                $indexColumn = $this->getDataIndexColumn();

                $rows = $this->getEntity()->getDbConnector()->selectRows(
                    $this->getTableName(),
                    new Expression(
                        "$referenceColumn = ? AND $indexColumn IN (?)",
                        array_merge([$this->getEntity()->getId()], [array_keys($dbIds)])
                    )
                );
                foreach ($rows as &$row) {
                    $id = $row[$indexColumn];
                    unset($row[$referenceColumn]);
                    $this->dbData[$id] = $this->localData[$id] = $row;
                    $this->initialized[$id] = 1;
                    if ($cache) {
                        $cache->set(
                            $dbIds[$id],
                            $row ?? [],
                            $this->getCacheTtl()
                        );
                    }
                    $results[$id] = $this->getReference($id);
                }
                if (count($rows) != count($dbIds)) {
                    $missing = [];
                    foreach ($results as $key => $value) {
                        if ($value === null) {
                            $missing[] = $key;
                        }
                    }
                    throw new ReferenceNotFoundException($this->getEntity(), implode(',', $missing));
                }
            }
        }
        return $results;
    }

    public function current()
    {
    }

    public function next()
    {
    }

    public function key()
    {
    }

    public function valid()
    {
        throw new \Exception('Iterate over big reference is disabled. Use getPage instead');
    }

    public function rewind()
    {
    }

    /**
     * @return EntityCacheIndex[]
     */
    public function getCacheIndices(): array
    {
        return [];
    }

    private function init(int $id)
    {
        if ($this->isMarkToClear() || isset($this->initialized[$id])) {
            return;
        }
        $cacheKey = null;
        if ($cache = $this->getCacheConnector()) {
            $cacheKey = $this->getCacheKey() . ':' . $id;
            $row = $cache->get($cacheKey);
            if ($row) {
                $this->dbData[$id] = $this->localData[$id] = $row;
                $this->initialized[$id] = true;
            }
            if ($row) {
                return;
            }
        }
        $row = $this->loadFromDatabase($id);
        if ($row) {
            $this->dbData[$id] = $this->localData[$id] = $row;
        }
        if ($cache) {
            $cache->set(
                $cacheKey,
                $row ?? [],
                $this->getCacheTtl()
            );
        }
        $this->initialized[$id] = true;
    }

    private function loadFromDatabase(int $id): array
    {
        $referenceColumn = $this->getReferenceColumn();
        $indexColumn = $this->getDataIndexColumn();

        $row = $this->getEntity()->getDbConnector()->selectRow(
            $this->getTableName(),
            new Expression(
                "$referenceColumn = ? AND $indexColumn= ?",
                [
                    $this->getEntity()->getId(),
                    $id
                ]
            )
        );
        unset($row[$referenceColumn]);
        return $row;
    }

    private function getIdsFromIndex(
        string $indexCode,
        bool $many,
        array $values,
        bool $initialize,
        ?Pager $pager,
        ?array $orderFields
    ): array {
        $index = $this->getCacheIndex($indexCode, !$many);
        $fields = $this->getCacheIndexValues($index, $values);

        $cache = $this->getEntity()::getCacheService();
        $ids = null;

        $cacheKey = self::getCacheKeyForIndex($indexCode, $fields);
        if ($orderFields !== null) {
            $cacheKey .= ':' . $this->getCacheKeyPartForSort($orderFields);
        }
        if ($pager) {
            $ids = [];
            $firstRow = ($pager->getCurrentPage() - 1) * $pager->getRowsOnPage();
            $lastRow = $firstRow + $pager->getRowsOnPage();
            $currentPage = $firstPage = (int) floor($firstRow / self::ROWS_IN_CACHED_PAGE) + 1;
            $lastPage = (int) ceil($lastRow / self::ROWS_IN_CACHED_PAGE);

            while ($currentPage <= $lastPage) {
                $cacheKeyWithPage = $cacheKey . ':' . $currentPage;
                $allIds = $cache->get($cacheKeyWithPage);
                $cachePager = null;
                if ($allIds === null) {
                    $cachePager = new Pager($currentPage, self::ROWS_IN_CACHED_PAGE);
                    $allIds = $this->searchIdsForIndex($index, $values, $fields, $cachePager, $orderFields);
                    $allIds = array_merge([$cachePager->getTotalRows()], $allIds);
                    $cache->set(
                        $cacheKeyWithPage,
                        $allIds,
                        Entity::CACHE_TTL
                    );
                }
                $totalRows = array_shift($allIds);
                $pager->setTotalRows($totalRows);

                $firstRowCache = ($currentPage - 1) * self::ROWS_IN_CACHED_PAGE;
                $lastRowCache = $firstRowCache + self::ROWS_IN_CACHED_PAGE;
                $sliceStart = 0;
                $sliceLength = $pager->getRowsOnPage();
                if ($currentPage == $firstPage) {
                    $sliceStart = $firstRow - $firstRowCache;
                } elseif ($currentPage == $lastPage) {
                    $sliceLength = $lastRowCache - $firstRow - $sliceStart;
                }
                $ids = array_merge($ids, array_slice($allIds, $sliceStart, $sliceLength));
                if ($cachePager && $cachePager->getTotalPages() <= $currentPage) {
                    break;
                }
                $currentPage++;
            }
        } else {
            $ids = $cache->get($cacheKey);
            if ($ids === null) {
                $ids = $this->searchIdsForIndex($index, $values, $fields, null, $orderFields);
                $ids = $ids ? $ids[0] : 0;
                $cache->set(
                    $cacheKey,
                    $ids,
                    Entity::CACHE_TTL
                );
            }
        }

        if ($ids === null) {
            $ids = $this->searchIdsForIndex($index, $values, $fields, null, $orderFields);
            if (!$many) {
                $ids = $ids ? $ids[0] : 0;
            }
        }
        if (!$ids) {
            return [];
        }
        if (!$initialize) {
            return $many ? (array) $ids : [$ids];
        }
        return $many ? $this->offsetGets((array) $ids) : [$this->offsetGet($ids)];
    }

    private function getCacheIndex(string $code, bool $unique): EntityCacheIndex
    {
        $indices = $this->getCacheIndicesInstances();
        if (!isset($indices[$code])) {
            throw new \InvalidArgumentException("Index $code not found");
        }
        $index = $indices[$code];
        if ($unique && !$index->isUnique()) {
            throw new \InvalidArgumentException("Index $code is not unique");
        } elseif (!$unique && $index->isUnique()) {
            throw new \InvalidArgumentException("Index $code is unique");
        }
        return $index;
    }

    /**
     * @return EntityCacheIndex[]
     */
    private function getCacheIndicesInstances(): array
    {
        static $indices = [];
        $key = get_called_class();
        if (!isset($indices[$key])) {
            $indices[$key] = $this->getCacheIndices();
        }
        return $indices[$key];
    }

    private function getCacheIndexValues(EntityCacheIndex $index, array $values): array
    {
        $fields = $index->getFields();
        if (count($fields) != count($values)) {
            throw new \InvalidArgumentException("Invalid values count in index");
        }
        return array_combine($fields, $values);
    }

    private function getCacheKeyForIndex(string $indexCode, array $fields): string
    {
        foreach ($fields as &$field) {
            if (is_bool($field)) {
                $field = $field ? 1 : 0;
            } elseif (is_null($field)) {
                $field = '_null_';
            }
        }
        $cacheKey = 'reference:index:' . $indexCode . ':'
            . $this->getEntity()->getDbConnectorCode() . ':' . $this->getTableName();
        return $cacheKey . ':' . md5(print_r($fields, true));
    }

    private function getCacheKeyPartForSort(array $sort): string
    {
        return substr(md5(strtolower(print_r($sort, true))), 0, 5);
    }

    private function searchIdsForIndex(
        EntityCacheIndex $index,
        array $values,
        array $fields,
        ?Pager $pager,
        ?array $orderFields
    ): array {
        $referenceColumn = $this->getReferenceColumn();
        $indexColumn = $this->getDataIndexColumn();
        $where = "$referenceColumn = ?";
        $bind = [$this->getEntity()->getId()];
        $iterator = 0;
        foreach (array_keys($fields) as $key) {
            $value = $values[$iterator++];
            if ($value === null) {
                $where .= " AND `$key` IS NULL";
            } else {
                $where .= " AND `$key` = ?";
                $bind[] = $value;
            }
        }
        $checkOrder = true;
        if ($orderFields === null) {
            if ($index instanceof SortableEntityCacheIndex) {
                $checkOrder = false;
                $orderFields = $index->getSortColumns()[0];
            }
        }
        if ($orderFields) {
            if ($index instanceof SortableEntityCacheIndex) {
                $orders = [];
                if ($checkOrder) {
                    $valid = false;
                    foreach ($index->getSortColumns() as $columns) {
                        if (array_keys($columns) === array_keys($orderFields)
                            && strtolower(print_r(array_values($columns), true)) === strtolower(print_r(array_values($orderFields), true))
                        ) {
                            $valid = true;
                            break;
                        }
                    }
                    if (!$valid) {
                        throw new \InvalidArgumentException('sort cache index is not defined for defined keys');
                    }
                }
                foreach ($orderFields as $key => $value) {
                    $orders[] = $key . ' ' . $value;
                }
                $where .= ' ORDER BY ' . implode(', ', $orders);
            } else {
                throw new \InvalidArgumentException('sort cache index is not defined');
            }
        }
        return $this->getEntity()->getDbConnector()->selectColumn(
            $this->getTableName(),
            $indexColumn,
            new Expression($where, $bind),
            $pager
        );
    }
}
