<?php
namespace MyHammer\Library\Entity;

use MyHammer\Library\Event\Entity\EntityAddedEvent;
use MyHammer\Library\Event\Entity\EntityAddingEvent;
use MyHammer\Library\Event\Entity\EntityDeletedEvent;
use MyHammer\Library\Event\Entity\EntityDeletingEvent;
use MyHammer\Library\Event\Entity\EntityUpdatedEvent;
use MyHammer\Library\Event\Entity\EntityUpdatingEvent;
use MyHammer\Library\Events;
use MyHammer\Library\Entity\Reference\BigReference;
use MyHammer\Library\Entity\Reference\ManyReference;
use MyHammer\Library\Entity\Reference\Row;
use MyHammer\Library\Entity\Reference\SmallReference;
use MyHammer\Library\Listener\RedisQueuesListener;
use MyHammer\Library\Service\CacheService;
use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Services;

class EntityFlusher
{
    use Services;

    /** @var Entity[] */
    private $toInsert = [];
    /** @var Entity[] */
    private $toUpdate = [];
    /** @var Entity[] */
    private $toDelete = [];

    public function registerEntity(Entity $entity): self
    {
        if ($entity->getId() && $entity->getDBData()) {
            $this->toUpdate[] = $entity;
            return $this;
        }
        $this->toInsert[] = $entity;
        return $this;
    }

    public function deleteEntity(Entity $entity): self
    {
        $this->toDelete[] = $entity;
        return $this;
    }

    public function flush()
    {
        /** @var CacheService[] $cacheServices */
        $cacheToDelete = $cacheServices = [];
        $this->handleInserts($cacheServices, $cacheToDelete);
        $this->handleUpdates($cacheServices, $cacheToDelete);
        $this->handleDeletes($cacheServices, $cacheToDelete);
        foreach ($cacheToDelete as $cacheId => $keys) {
            $cache = $cacheServices[$cacheId];
            if ($cache !== null) {
                $cache->deleteMany(array_keys($keys));
            }
        }
    }

    public function flushLazy()
    {
        foreach ($this->toInsert as $key => $entity) {
            $changesDb = $entity->getUpdateBind();
            $queueValue = $this->preparePackageForQueue($entity, $changesDb, 0);
            $this->serviceQueue()->add(RedisQueuesListener::QUEUE_ENTITY_LAZY_FLUSH, $queueValue);
            unset($this->toInsert[$key]);
        }
        foreach ($this->toUpdate as $key => $entity) {
            if ($entity->isDirty()) {
                $changesDb = $entity->getUpdateBind();
                $queueValue = $this->preparePackageForQueue($entity, $changesDb, $entity->getId());
                $this->serviceQueue()->add(RedisQueuesListener::QUEUE_ENTITY_LAZY_FLUSH, $queueValue);
            }
            unset($this->toUpdate[$key]);
        }
        foreach ($this->toDelete as $key => $entity) {
            $queueValue = [get_class(), $entity->getId() * -1, []];
            $this->serviceQueue()->add(RedisQueuesListener::QUEUE_ENTITY_LAZY_FLUSH, $queueValue);
            unset($this->toDelete[$key]);
        }
    }

    private function handleInserts(array &$cacheServices, array &$cacheToDelete)
    {
        if (!$this->toInsert) {
            return;
        }
        foreach ($this->groupEntities($this->toInsert) as $group) {
            /** @var Entity[] $entities */
            foreach ($group as $cacheId => $entities) {
                $binds = [];
                $class = get_class($entities[0]);
                $definition = $entities[0]->getEntityDefinition();
                if ($definition[0] && $cacheId) {
                    $cacheToDelete[$cacheId]['entity:all:' . $class] = 1;
                }
                $cacheServices[$cacheId] = $entities[0]::getCacheService();
                foreach ($entities as $entity) {
                    $bind = $entity->getUpdateBind();
                    $event = new EntityAddingEvent($entity, $bind);
                    $this->serviceEventDispatcher()->dispatch($class . ':' . Events::ENTITY_ADDING, $event);
                    $bind = $event->getBind();
                    $binds[] = $bind;
                }
                $id = $entities[0]::getDbConnector()->insertMany($entities[0]::getTableName(), $binds, empty($binds['id']));
                foreach ($entities as $index => $entity) {
                    $bind = $binds[$index];
                    $bind['id'] = $id++;
                    $entity->markAsFlushed($bind);
                    if ($entity::getCacheService()) {
                        $key = 'entity:' . $entity::getDbConnectorCode() . ':' . $entity::getTableName() . ':{' . $entity->getId() . '}';
                        $cacheToDelete[$cacheId][$key] = 1;
                        foreach ($this->updateIndexes($entity, $bind, [], true, false) as $key) {
                            $cacheToDelete[$cacheId][$key] = 1;
                        }
                    }
                    foreach ($entity->getReferences() as $reference) {
                        $this->handleReference($reference, $cacheId, $cacheToDelete);
                    }
                    $this->serviceEventDispatcher()->dispatch($class . ':' . Events::ENTITY_ADDED, new EntityAddedEvent($entity));
                }
            }
        }
        $this->toInsert = [];
    }

    private function handleUpdates(array &$cacheServices, array &$cacheToDelete)
    {
        if (!$this->toUpdate) {
            return;
        }
        foreach ($this->groupEntities($this->toUpdate) as $group) {
            /** @var Entity[] $entities */
            foreach ($group as $cacheId => $entities) {
                $cacheServices[$cacheId] = $entities[0]::getCacheService();
                $class = get_class($entities[0]);
                $changed = false;
                foreach ($entities as $entity) {
                    $bind = $entity->getUpdateBind();
                    if ($bind) {
                        $changed = true;
                        $dbData = $entity->getDBData();
                        $event = new EntityUpdatingEvent($entity, $bind, $dbData);
                        $this->serviceEventDispatcher()->dispatch(
                            $class . ':' . Events::ENTITY_UPDATING,
                            $event
                        );
                        $bind = $event->getBind();
                        $entity->getDbConnector()->update(
                            $entity->getTableName(),
                            $bind,
                            new Expression('id = ?', [$entity->getId()])
                        );
                        $newDbData = array_replace($dbData, $bind);
                        $entity->markAsFlushed($newDbData);
                        if ($entity::getCacheService()) {
                            $key = 'entity:' . $entity::getDbConnectorCode() . ':' . $entity::getTableName() . ':{' . $entity->getId() . '}';
                            $cacheToDelete[$cacheId][$key] = 1;
                            foreach ($this->updateIndexes($entity, $bind, $dbData, false, false) as $key) {
                                $cacheToDelete[$cacheId][$key] = 1;
                            }
                        }
                    }
                    $referenceChanges = [];
                    foreach ($entity->getChangedReferences() as $code => $reference) {
                        if ($reference->isMarkToClear()) {
                            $this->handleRemoveReference($reference, [$entity->getId()], $cacheId, $cacheToDelete);
                        }
                        $referenceChanges[$code] = $this->handleReference($reference, $cacheId, $cacheToDelete);
                    }
                    $this->serviceEventDispatcher()->dispatch(
                        $class . ':' . Events::ENTITY_UPDATED,
                        new EntityUpdatedEvent($entity, $bind, $referenceChanges)
                    );
                }
                if ($changed && $cacheId) {
                    $definition = $entities[0]->getEntityDefinition();
                    if ($definition[0]) {
                        $cacheToDelete[$cacheId]['entity:all:' . $class] = 1;
                    }
                    $cacheServices[$cacheId] = $entities[0]::getCacheService();
                }
            }
        }
        $this->toUpdate = [];
    }

    private function handleDeletes(array &$cacheServices, array &$cacheToDelete)
    {
        if (!$this->toDelete) {
            return;
        }
        foreach ($this->groupEntities($this->toDelete) as $group) {
            /** @var Entity[] $entities */
            foreach ($group as $cacheId => $entities) {
                $definition = $entities[0]->getEntityDefinition();
                $class = get_class($entities[0]);
                if ($definition[0] && $cacheId) {
                    $cacheToDelete[$cacheId]['entity:all:' . $class] = 1;
                }
                $cacheServices[$cacheId] = $entities[0]::getCacheService();
                $allFields = $ids = [];
                foreach ($entities as $entity) {
                    $this->serviceEventDispatcher()->dispatch($class . ':' . Events::ENTITY_DELETING, new EntityDeletingEvent($entity));
                    $allFields[] = $entity->getFields();
                    $ids[] = $entity->getId();
                }
                $entities[0]->getDbConnector()->delete(
                    $entities[0]->getTableName(),
                    new Expression('id IN (?)', [$ids])
                );
                foreach ($entities[0]->getReferences() as $reference) {
                    $this->handleRemoveReference($reference, $ids, $cacheId, $cacheToDelete);
                }
                foreach ($entities as $entity) {
                    $key = 'entity:' . $entity::getDbConnectorCode() . ':' . $entity::getTableName() . ':{' . $entity->getId() . '}';
                    $cacheToDelete[$cacheId][$key] = 1;
                    foreach ($this->updateIndexes($entity, $entity->getFields(), [], false, true) as $key) {
                        $cacheToDelete[$cacheId][$key] = 1;
                    }
                    $this->serviceEventDispatcher()->dispatch($class . ':' . Events::ENTITY_DELETED, new EntityDeletedEvent($entity));
                    $entity::clearLocalCache($entity->getId());
                }
            }
        }
        $this->toDelete = [];
    }

    private function groupEntities(array $entities): array
    {
        $grouped = [];
        /** @var Entity $entity */
        foreach ($entities as $entity) {
            $connectorCode = $entity::getDbConnectorCode() . $entity::getTableName();
            $cacheCode = $entity::getCacheService() ? spl_object_hash($entity::getCacheService()) : '';
            if (!isset($connectorCode)) {
                $grouped[$connectorCode] = [];
            }
            $grouped[$connectorCode][$cacheCode][] = $entity;
        }
        return $grouped;
    }

    private function updateIndexes(Entity $entity, array $changes, array $currentData, bool $newRow, bool $deleteRow): array
    {
        $keys = [];
        foreach ($this->getCacheIndicesInstances($entity) as $code => $index) {
            foreach ($index->getFields() as $field) {
                $sorts = [];
                if ($index instanceof SortableEntityCacheIndex) {
                    foreach ($index->getSortColumns() as $columns) {
                        if ($newRow || $deleteRow || isset($columns[$field])) {
                            $sorts[] = $columns;
                        }
                    }
                }
                if ($sorts || array_key_exists($field, $changes)) {
                    $sorts[] = [];
                    $bindOld = $bindNew = [];
                    foreach ($index->getFields() as $key) {
                        $bindOld[$key] = $currentData[$key] ?? null;
                        $bindNew[$key] = $changes[$key] ?? ($currentData[$key] ?? null);
                    }
                    foreach ($sorts as $sort) {
                        $sortKey = $sort ? (':' . $this->getCacheKeyPartForSort($sort)) : '';
                        if (!$newRow) {
                            $cacheKeyOld = $this->getCacheKeyForIndex(
                                $entity,
                                $code,
                                $this->getCacheIndexValues($index, $bindOld)
                            ) . $sortKey;

                            if (!$index->isUnique()) {
                                for ($i = 1; $i <= $index->getMaxCachePages(); $i++) {
                                    $keys[] = $cacheKeyOld . ':' . $i;
                                }
                            } else {
                                $keys[] = $cacheKeyOld;
                            }
                        }
                        $cacheKeyNew = $this->getCacheKeyForIndex(
                            $entity,
                            $code,
                            $this->getCacheIndexValues($index, $bindNew)
                        ) . $sortKey;
                        if (!$index->isUnique()) {
                            for ($i = 1; $i <= $index->getMaxCachePages(); $i++) {
                                $keys[] = $cacheKeyNew . ':' . $i;
                            }
                        } else {
                            $keys[] = $cacheKeyNew;
                        }
                    }
                    continue(2);
                }
            }
        }
        return $keys;
    }

    private function getCacheIndicesInstances(Entity $entity): array
    {
        static $indices = [];
        $key = get_class($entity);
        if (!isset($indices[$key])) {
            $indices[$key] = $entity::getCacheIndices();
        }
        return $indices[$key];
    }

    private function getCacheIndicesInstancesBigReference(BigReference $reference): array
    {
        static $indicesBigReference = [];
        $key = get_class($reference);
        if (!isset($indicesBigReference[$key])) {
            $indicesBigReference[$key] = $reference->getCacheIndices();
        }
        return $indicesBigReference[$key];
    }

    private function getCacheKeyPartForSort(array $sort): string
    {
        return substr(md5(strtolower(print_r($sort, true))), 0, 5);
    }

    private function getCacheKeyForIndex(Entity $entity, string $indexCode, array $fields): string
    {
        foreach ($fields as &$field) {
            if (is_bool($field)) {
                $field = $field ? 1 : 0;
            } elseif (is_null($fields)) {
                $field = '_null_';
            }
        }
        $cacheKey = 'entity:index:' . $indexCode . ':'
            . $entity->getDbConnectorCode() . ':' . $entity::getTableName();
        return $cacheKey . ':' . md5(print_r($fields, true));
    }

    private function getCacheKeyForIndexInBigReference(BigReference $reference, string $indexCode, array $fields): string
    {
        foreach ($fields as &$field) {
            if (is_bool($field)) {
                $field = $field ? 1 : 0;
            } elseif (is_null($field)) {
                $field = '_null_';
            }
        }
        $cacheKey = 'reference:index:' . $indexCode . ':'
            . $reference->getEntity()->getDbConnectorCode() . ':' . $reference->getTableName();
        return $cacheKey . ':' . md5(print_r($fields, true));
    }

    private function getCacheIndexValues(EntityCacheIndex $index, array $values): array
    {
        $fields = $index->getFields();
        if (count($fields) != count($values)) {
            throw new \InvalidArgumentException("Invalid values count in index");
        }
        return array_combine($fields, $values);
    }

    protected function handleReference(ManyReference $reference, string $cacheId, array &$cacheToDelete): array
    {
        $changes = [];
        $changed = $reference->getChanges();
        /** @var Row[] $insertRows */
        $insertBinds = $insertRows = $deleteRows = [];
        $deleteIds = [];
        $needId = false;
        $indexColumn =  $reference->getDataIndexColumn();
        foreach ($changed as $key => $row) {
            $changes[$key] = $data = $row->getChanges();
            if ($indexColumn == 'id' && $key < 0) {
                $needId = true;
            }
            $raw = $row->getRawData();
            if ($row->isToRemove()) {
                $deleteIds[] = $raw[$indexColumn];
                $deleteRows[$key] = $raw;
            } elseif (!$row->isInDb()) {
                if ($needId) {
                    unset($data['id']);
                }
                $data[$reference->getReferenceColumn()] = $reference->getEntity()->getId();
                $row->setField($reference->getReferenceColumn(), $reference->getEntity()->getId());
                $raw[$reference->getReferenceColumn()] = $reference->getEntity()->getId();
                $insertBinds[$key] = $data;
                $insertRows[$key] = $row;
            } else {
                $referenceColumn = $reference->getReferenceColumn();
                $reference->getEntity()->getDbConnector()->update(
                    $reference->getTableName(),
                    $data,
                    new Expression(
                        "$indexColumn = ? AND $referenceColumn = ? LIMIT 1",
                        [$raw[$indexColumn], $reference->getEntity()->getId()]
                    )
                );
                $row->setIsInDb();
                if ($reference instanceof BigReference) {
                    $this->updateBigReferenceIndexes($reference, $changes[$key], $raw, false, $cacheId, $cacheToDelete);
                }
            }
        }
        $dataToSet = $keysToUnset = [];
        if ($insertBinds) {
            $id = $reference->getDbConnector()->insertMany(
                $reference->getTableName(),
                array_values($insertBinds),
                $needId
            );
            foreach ($insertRows as $key => $row) {
                $data = $insertBinds[$key];
                if ($needId) {
                    $row->setField('id', $id++);
                    $data['id'] = $row->getField('id');
                    $keysToUnset[] = $key;
                }
                unset($data[$reference->getReferenceColumn()]);
                $dataToSet[$data[$reference->getDataIndexColumn()]] = $data;
                $row->setIsInDb();
                if ($reference instanceof BigReference) {
                    $this->updateBigReferenceIndexes($reference, $row->getRawData(), [], true, $cacheId, $cacheToDelete);
                }
            }
        }
        if ($deleteIds) {
            $referenceColumn = $reference->getReferenceColumn();
            $reference->getEntity()->getDbConnector()->delete(
                $reference->getTableName(),
                new Expression(
                    "$indexColumn IN (?) AND $referenceColumn = ? LIMIT 1",
                    [$deleteIds, $reference->getEntity()->getId()]
                )
            );
            foreach ($deleteRows as $key => $deleteRow) {
                 $keysToUnset[] = $key;
                if ($reference instanceof BigReference) {
                    $this->updateBigReferenceIndexes($reference, $deleteRow, [], false, $cacheId, $cacheToDelete);
                }
                $changes[$key] = null;
            }
        }
        $reference->markAsFlushed($dataToSet, $keysToUnset);
        $cacheId && $this->clearReferenceCache($reference, $changed, $cacheId, $cacheToDelete);
        return $changes;
    }

    protected function handleRemoveReference(ManyReference $reference, array $ids, string $cacheId, array &$cacheToDelete)
    {
        if ($cacheId) {
            if ($reference instanceof SmallReference) {
                $cacheToDelete[$cacheId][$this->getReferenceCacheKey($reference)] = 1;
            } elseif ($reference instanceof BigReference) {
                $referenceColumn = $reference->getReferenceColumn();
                $indexColumn = $reference->getDataIndexColumn();
                $db = $reference->getEntity()->getDbConnector();
                $where = "$referenceColumn IN (?)";
                $prefix = $this->getReferenceCacheKey($reference);
                foreach ($db->iterateRows($reference->getTableName(), new Expression($where, [$ids]), [$indexColumn]) as $row) {
                    $cacheToDelete[$cacheId][$prefix . ':' . $row[$indexColumn]] = 1;
                }
            }
        }
        $reference->getEntity()->getDbConnector()->delete(
            $reference->getTableName(),
            new Expression($reference->getReferenceColumn() . ' IN (?)', [$ids])
        );
    }

    private function clearReferenceCache(ManyReference $reference, array $rows, string $cacheId, array &$cacheToDelete)
    {
        $cache = $reference->getEntity()::getCacheService();
        if (!$cache) {
            return;
        }
        if ($reference instanceof SmallReference) {
            $cacheToDelete[$cacheId][$this->getReferenceCacheKey($reference)] = 1;
        } elseif ($reference instanceof BigReference) {
            foreach ($rows as $row) {
                $cacheKey = $this->getReferenceCacheKey($reference)
                    . ':' . $row->getRawData()[$reference->getDataIndexColumn()];
                $cache->delete($cacheKey);
                $cacheToDelete[$cacheId][$cacheKey] = 1;
            }
            $this->clearBigReferencePages($reference, $cacheId, $cacheToDelete);
        }
    }

    private function getReferenceCacheKey(ManyReference $reference): string
    {
        return 'entity:reference:' . $reference->getEntity()->getDbConnector()->getConnectionCode()
            . ':' . $reference->getEntity()->getTableName() . ':' . $reference->getCodeName()
            . ':{' . $reference->getEntity()->getId() . '}';
    }

    private function clearBigReferencePages(BigReference $reference, string $cacheId, array &$cacheToDelete)
    {
        $cache = $reference->getEntity()::getCacheService();
        $fieldName = $reference->getReferenceColumn();
        $keyPrefix = 'entity:m:refs:' . md5(get_class($reference->getEntity()) . $reference->getCodeName() . $fieldName)
            . ':' . $reference->getEntity()->getId() . ':';
        $page1Key = $keyPrefix . 'page:1';
        $values = $cache->get($page1Key);
        if (!$values) {
            return;
        }
        $exploded = explode(',', $values);
        $pages = array_shift($exploded);
        for ($i = 1; $i <= $pages; $i++) {
            $cacheToDelete[$cacheId][$keyPrefix . 'page:' . $i] = 1;
            $prefix = $this->getReferenceCacheKey($reference);
            foreach ($exploded as $id) {
                $cacheToDelete[$cacheId][$keyPrefix . 'page:' . $i] = 1;
                $cacheToDelete[$cacheId][$prefix . ':' . $id] = 1;
            }
        }
    }

    protected function updateBigReferenceIndexes(
        BigReference $reference,
        array $changes,
        array $currentData,
        bool $newRow,
        string $cacheId,
        array &$cacheToDelete
    ) {
        foreach ($this->getCacheIndicesInstancesBigReference($reference) as $code => $index) {
            foreach ($index->getFields() as $field) {
                $sorts = [];
                if ($index instanceof SortableEntityCacheIndex) {
                    foreach ($index->getSortColumns() as $columns) {
                        if ($newRow || isset($columns[$field])) {
                            $sorts[] = $columns;
                        }
                    }
                }
                if ($sorts || array_key_exists($field, $changes)) {
                    $sorts[] = [];
                    $bindOld = $bindNew = [];
                    foreach ($index->getFields() as $key) {
                        $bindOld[$key] = $currentData[$key] ?? null;
                        $bindNew[$key] = $changes[$key] ?? ($currentData[$key] ?? null);
                    }
                    foreach ($sorts as $sort) {
                        $sortKey = $sort ? (':' . $this->getCacheKeyPartForSort($sort)) : '';
                        if (!$newRow) {
                            $cacheKeyOld = $this->getCacheKeyForIndexInBigReference(
                                $reference,
                                $code,
                                $this->getCacheIndexValues($index, $bindOld)
                            ) . $sortKey;

                            if (!$index->isUnique()) {
                                for ($i = 1; $i <= $index->getMaxCachePages(); $i++) {
                                    $cacheToDelete[$cacheId][$cacheKeyOld . ':' . $i] = 1;
                                }
                            } else {
                                $cacheToDelete[$cacheId][$cacheKeyOld] = 1;
                            }
                        }
                        $cacheKeyNew = $this->getCacheKeyForIndexInBigReference(
                            $reference,
                            $code,
                            $this->getCacheIndexValues($index, $bindNew)
                        ) . $sortKey;
                        if (!$index->isUnique()) {
                            for ($i = 1; $i <= $index->getMaxCachePages(); $i++) {
                                $cacheToDelete[$cacheId][$cacheKeyNew . ':' . $i] = 1;
                            }
                        } else {
                            $cacheToDelete[$cacheId][$cacheKeyNew] = 1;
                        }
                    }
                    continue(2);
                }
            }
        }
    }

    private function preparePackageForQueue(Entity $entity, array $changesDb, int $id): array
    {
        $references = [];
        $index = -1;
        foreach ($entity->getChangedReferences() as $code => $reference) {
            $changes = [];
            foreach ($reference->getChanges() as $key => $row) {
                $data = $row->getRawData();
                if ($row->isToRemove()) {
                    $data['__remove__row__'] = true;
                }
                $changes[$row->isInDb() ? $key : ($index--)] = $data;
            }
            $references[$code] = $changes;
        }
        return [get_class($entity), $id, $changesDb, $references];
    }
}
