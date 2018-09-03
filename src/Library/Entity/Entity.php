<?php
namespace MyHammer\Library\Entity;

use Assert\Assert;
use Assert\LazyAssertion;
use Assert\LazyAssertionException;
use MyHammer\Library\Entity\Exception\EntityNotFoundException;
use MyHammer\Library\Entity\Reference\ManyReference;
use MyHammer\Library\Entity\Schema\BaseReferenceIntColumn;
use MyHammer\Library\Entity\Schema\JSONColumn;
use MyHammer\Library\Entity\Schema\ReferenceJsonColumn;
use MyHammer\Library\Entity\Schema\TableSchema;
use MyHammer\Library\Service;
use MyHammer\Library\Listener\RedisQueuesListener;
use MyHammer\Library\Service\CacheService;
use MyHammer\Library\Service\ErrorLogService;
use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Service\Mysql\Pager;
use MyHammer\Library\Service\MysqlService;
use MyHammer\Library\Service\TimeService;
use MyHammer\Library\Services;
use MyHammer\Library\Supernova;

abstract class Entity implements DirtyInterface
{
    use FieldMapperTrait;

    public static $cache = [];
    public static $instancesCache = [];
    public static $counter = 0;

    public const ROWS_IN_CACHED_PAGE = 1000;
    public const CACHE_TTL = 2592000;

    private $dbData;
    private $localData;
    private $changes = [];
    private $subs = [];
    private $references = [];
    private $services = [];
    private $hasCacheOnlyFields = false;
    private $isLoadedFromCache = false;

    protected function __construct(array $data = null)
    {
        if ($data !== null) {
            $this->dbData = $this->localData = $data;
            $this->isLoadedFromCache = true;
        } elseif ($defaults = self::getTableSchema()->getDefaults($this->getDbConnector()->getConnectionUri())) {
            $this->changes = $this->localData = $defaults;
        }
        $this->hasCacheOnlyFields = static::getCacheFieldsProviderClass() !== null;
    }

    final public static function getCacheService(): ?CacheService
    {
        $code = static::getCacheConnectorCode();
        if ($code) {
            /** @var CacheService $service */
            $service  = Supernova::getContainer()->get($code);
            return $service;
        }
        return null;
    }

    /**
     * @param int $id
     * @param bool $toUpdate set it to true if entity will be changed and flushed. It will reduce number
     * of request to cache.
     * @return $this
     * @throws EntityNotFoundException
     */
    final public static function getById(int $id)
    {
        $class = get_called_class();
        $key = $class . ':' . $id;
        if (!isset(self::$cache[$key])) {
            /**
             * @var Entity $entity
             */
            $entity = new $class();
            $entity->initById($id);
        }
        return self::$cache[$key];
    }

    /**
     * @param array $ids
     * @return $this[]
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    final public static function getByIds(array $ids): array
    {
        $results = [];
        $missingIds = [];
        /**
         * @var Entity $class
         */
        $class = get_called_class();
        $dbConnector = static::getDbConnector();
        foreach ($ids as $id) {
            $id = (int) $id;
            $key = $class . ':' . $id;
            if (isset(self::$cache[$key])) {
                $results[$id] = self::$cache[$key];
            } else {
                $results[$id] = null;
                $cacheKey = self::getCacheKey($id, static::getDbConnectorCode(), static::getTableName());
                $missingIds[$cacheKey] = $id;
            }
        }
        if (!$missingIds) {
            return $results;
        }
        if ($cache = self::getCacheService()) {
            foreach ($cache->gets(array_keys($missingIds)) as $cacheKey => $value) {
                if ($value !== null) {
                    $entity = self::addToLocalCacheIfNeeded($value);
                    $results[$missingIds[$cacheKey]] = $entity;
                    unset($missingIds[$cacheKey]);
                }
            }
        }
        if ($missingIds) {
            $rows = $dbConnector->selectRows(
                static::getTableName(),
                new Expression('id IN (?)', [array_values($missingIds)])
            );
            /** @var EntityCacheFieldsProvider $provider */
            $provider = null;
            if ($cache && ($providerClass = static::getCacheFieldsProviderClass())) {
                $provider = new $providerClass();
            }
            foreach ($rows as $row) {
                $entity = self::addToLocalCacheIfNeeded($row);
                $cacheKey = self::getCacheKey($row['id'], static::getDbConnectorCode(), static::getTableName());
                $results[$missingIds[$cacheKey]] = $entity;
                unset($missingIds[$cacheKey]);
                if ($cache) {
                    if ($provider) {
                        foreach ($provider->getCachedFields($entity) as $key => $value) {
                            $entity->setInLocalData('cached:' . $key, $value);
                        }
                    }
                    $cache->set($cacheKey, $entity->getFields(), self::CACHE_TTL);
                }
            }
            if ($missingIds) {
                throw new EntityNotFoundException($class, ...array_values($missingIds));
            }
        }
        return $results;
    }

    final public static function getTableSchema(): TableSchema
    {
        static $tableSchemas = [];
        $class = get_called_class();
        if (!isset($tableSchemas[$class])) {
            $tableSchema = static::getTableSchemaDefinition();
            $tableSchemas[$class] = $tableSchema;
        }
        return $tableSchemas[$class];
    }

    final public static function clearLocalCache($id = null)
    {
        if ($id) {
            $key = get_called_class() . ':' . $id;
            if (isset(self::$cache[$key])) {
                self::$counter--;
                unset(self::$cache[$key]);
            }
        } else {
            self::$cache = [];
        }
    }

    /**
     * @return $this[]
     * @throws \Exception
     */
    final public static function getAll(): array
    {
        if (!static::cacheAll()) {
            throw new \InvalidArgumentException("getting all is not enabled");
        }
        $key = 'entity:all:' . get_called_class();
        $ids = null;
        if ($cache = self::getCacheService()) {
            $ids = $cache->getWithClosure(
                $key,
                self::CACHE_TTL,
                function () {
                    return static::getDbConnector()->selectColumn(
                        static::getTableName(),
                        'id',
                        new Expression('1')
                    );
                }
            );
        }
        if ($ids === null) {
            $ids = static::getDbConnector()->selectColumn(
                static::getTableName(),
                'id',
                new Expression('1')
            );
        }
        return $ids ? self::getByIds($ids) : [];
    }

    /**
     * @param Expression $expression
     * @return $this|null
     * @throws EntityNotFoundException
     */
    final public static function getOneByQuery(Expression $expression): ?self
    {
        if (self::getCacheService()) {
            $id = (int)static::getDbConnector()->selectField(
                self::getTableName(),
                'id',
                $expression
            );
            return $id ? self::getById($id) : null;
        }
        $row = static::getDbConnector()->selectRow(
            self::getTableName(),
            $expression
        );
        if (!$row) {
            return null;
        }
        return self::addToLocalCacheIfNeeded($row);
    }

    /**
     * @param Expression $expression
     * @param Pager $pager
     * @return int[]
     */
    final public static function getManyIdsByQuery(Expression $expression, Pager $pager): array
    {
        return static::getDbConnector()->selectColumn(
            self::getTableName(),
            'id',
            $expression,
            $pager
        );
    }

    /**
     * @param Expression $expression
     * @param Pager $pager
     * @return $this[]
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    final public static function getManyByQuery(Expression $expression, Pager $pager): array
    {
        if (self::getCacheService()) {
            $ids = self::getManyIdsByQuery($expression, $pager);
            return $ids ? self::getByIds($ids) : [];
        }
        $rows = static::getDbConnector()->selectRows(
            self::getTableName(),
            $expression,
            $pager
        );
        $results = [];
        foreach ($rows as $row) {
            $results[(int)$row['id']] = self::addToLocalCacheIfNeeded($row);
        }
        return $results;
    }

    /**
     * @param string $indexCode
     * @param array ...$value
     * @return $this
     */
    final public static function getOneByIndex(string $indexCode, ...$value): ?self
    {
        return self::getIdsFromIndex($indexCode, false, $value, true, null, null)[0] ?? null;
    }

    /**
     * @param string $indexCode
     * @param array ...$value
     * @return int
     */
    final public static function getOneIdByIndex(string $indexCode, ...$value): int
    {
        return self::getIdsFromIndex($indexCode, false, $value, false, null, null)[0];
    }

    /**
     * @param string $indexCode
     * @param Pager $pager
     * @param array ...$value
     * @return $this[]
     */
    final public static function getManyByIndex(string $indexCode, Pager $pager, ...$value): array
    {
        return self::getIdsFromIndex($indexCode, true, $value, true, $pager, null);
    }

    /**
     * @param string $indexCode
     * @param Pager $pager
     * @param array $values
     * @param array $sort
     * @return $this[]
     */
    final public static function getManyByIndexWithSort(string $indexCode, Pager $pager, array $values, array $sort): array
    {
        return self::getIdsFromIndex($indexCode, true, $values, true, $pager, $sort);
    }

    /**
     * @param string $indexCode
     * @param Pager $pager
     * @param array ...$value
     * @return int[]
     */
    final public static function getManyIdsByIndex(string $indexCode, Pager $pager, ...$value): array
    {
        return self::getIdsFromIndex($indexCode, true, $value, false, $pager, null);
    }

    final public static function getManyIdsByIndexWithSort(string $indexCode, Pager $pager, array $values, array $sort): array
    {
        return self::getIdsFromIndex($indexCode, true, $values, false, $pager, $sort);
    }

    final public static function clearFromCache(int $id)
    {
        if ($cache = self::getCacheService()) {
            $cache->delete(self::getCacheKey($id, static::getDbConnectorCode(), static::getTableName()));
        }
    }

    /**
     * @return $this
     */
    final public static function newInstance()
    {
        $class = get_called_class();
        /**
         * @var Entity $entity
         */
        $entity = new $class();
        return $entity;
    }

    final public static function getTableName(): string
    {
        static $tableNames = [];
        $class = get_called_class();
        if (!isset($tableNames[$class])) {
            $tableSchema = static::getTableSchemaDefinition();
            $tableNames[$class] = $tableSchema->getTableName();
        }
        return $tableNames[$class];
    }


    abstract protected static function getTableSchemaDefinition(): TableSchema;

    final public function getId(): ?int
    {
        return $this->getField('id');
    }

    final public function markAsFlushed(array $dbData)
    {
        $this->changes = [];
        $this->localData = $this->dbData = $dbData;
        $this->isLoadedFromCache = false;
    }

    public function getField(string $key, bool $cacheOnlyField = false)
    {
        if ($cacheOnlyField) {
            $key = 'cached:' . $key;
            if (!$this->isLoadedFromCache) {
                $changes = $this->changes;
                $this->initById($this->getId());
                $this->setFields($changes);
            }
        }
        return $this->localData[$key] ?? null;
    }

    public function getTranslatedField(string $key, string $lang = null): ?string
    {
        $lang = $lang ?? $this->serviceLocale()->getCurrentLanguage();

        if ($value = $this->getField($key . '_' . $lang)) {
            return $value;
        }

        return $this->getField($key);
    }

    /**
     * @param string $key
     * @param $value
     * @return Entity|mixed
     */
    public function setField(string $key, $value): self
    {
        $this->localData[$key] = $value;
        $left = $this->dbData[$key] ?? null;
        $right = $value;
        if ($left === null && $right !== null) {
            $this->changes[$key] = $value;
        } elseif ($right === null && $left !== null) {
            $this->changes[$key] = $value;
        } elseif ($left != $value) {
            $this->changes[$key] = $value;
        } else {
            unset($this->changes[$key]);
        }
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @param $lang
     * @return Entity|mixed
     */
    public function setTranslatedField(string $key, $value, $lang): self
    {
        return $this->setField($key . '_' . $lang, $value);
    }

    public function setInLocalData(string $key, $value): self
    {
        $this->localData[$key] = $value;
        return $this;
    }

    public function setFields(array $fields): self
    {
        $columns = self::getTableSchema()->getColumns();
        foreach ($fields as $key => $value) {
            if (isset($columns[$key]) && $columns[$key] instanceof JSONColumn) {
                $this->mapFromArrayToJson($key, $value);
            } else {
                $this->setField($key, $value);
            }
        }
        return $this;
    }

    public function getFields(): array
    {
        return $this->localData ?? [];
    }

    public function getDBData(): array
    {
        return $this->dbData ?? [];
    }

    public function getChangedFields(): array
    {
        return $this->changes;
    }

    /**
     * @return ManyReference[]
     */
    public function getChangedReferences(): array
    {
        $changed = [];
        /**
         * @var ManyReference $reference
         */
        foreach ($this->references as $key => $reference) {
            if ($reference->isDirty()) {
                $changed[$key] = $reference;
            }
        }
        return $changed;
    }

    public function isReferenceChanged(string $code): bool
    {
        return isset($this->getChangedReferences()[$code]);
    }

    public function isDirty(): bool
    {
        return !empty($this->changes) || $this->getChangedReferences();
    }

    public function flush()
    {
        if (!$this->isDirty()) {
            return;
        }
        (new EntityFlusher())->registerEntity($this)->flush();
    }

    public function flushLazy()
    {
        if (!$this->isDirty()) {
            return;
        }
        (new EntityFlusher())->registerEntity($this)->flushLazy();
    }

    public function delete()
    {
        (new EntityFlusher())->deleteEntity($this)->flush();
    }

    public function deleteLazy()
    {
        (new EntityFlusher())->deleteEntity($this)->flushLazy();
    }

    public function clearCache()
    {
        $cache = self::getCacheService();
        if (!$cache || !$this->getId()) {
            return;
        }
        $cache->delete($this->getCacheKey($this->getId(), $this->getDbConnectorCode(), $this->getTableName()));
        foreach ($this->getReferences() as $reference) {
            $reference->clearCache();
        }
    }

    /**
     * @param string $key
     * @param string $class
     * @return Reference|mixed
     */
    public function getReference(string $key, string $class): Reference
    {
        if (isset($this->references[$key])) {
            return $this->references[$key];
        }
        /**
         * @var Reference $object
         */
        $object = $this->references[$key] = new $class(
            $this,
            $key,
            $this->getDbConnector(),
            self::getCacheService(),
            self::CACHE_TTL
        );
        return $object;
    }

    /**
     * @return ManyReference[]
     */
    public function getReferences(): array
    {
        $codes = $this->serviceCache()->getLocal()->getWithClosure(
            'entity:references:' . get_called_class(),
            TimeService::YEAR,
            function () {
                $reflection = new \ReflectionObject($this);
                $return = [];
                foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if (strpos($method->getName(), 'get') !== 0) {
                        continue;
                    }
                    if ($method->isStatic() || $method->isAbstract() || !$method->getReturnType()) {
                        continue;
                    }
                    if ($method->getReturnType()->isBuiltin()) {
                        continue;
                    }
                    $returnType = (string)$method->getReturnType();
                    $classReflection = new \ReflectionClass($returnType);
                    if (!$classReflection->isSubclassOf(Reference::class)) {
                        continue;
                    }
                    $methodName = $method->getName();
                    /**
                     * @var Reference $reference
                     */
                    $reference = $this->$methodName();
                    $return[$reference->getCodeName()] = $returnType;
                }
                return $return;
            }
        );
        $references = [];
        foreach ($codes as $code => $class) {
            $references[$code] = $this->getReference($code, $class);
        }
        return $references;
    }

    /**
     * @param string $key
     * @param string $class
     * @param array ...$parameters
     * @return Sub|mixed
     */
    public function sub(string $key, string $class, ...$parameters): Sub
    {
        if (isset($this->subs[$key])) {
            return $this->subs[$key];
        }
        /**
         * @var Sub $object
         */
        $object = $this->subs[$key] = new $class($this, $parameters);
        return $object;
    }

    public function getLazyQueueCode(): string
    {
        return RedisQueuesListener::QUEUE_ENTITY_LAZY_FLUSH;
    }

    final public static function getDbConnector(): MysqlService
    {
        /** @var MysqlService $db */
        $db = Supernova::getContainer()->get(static::getDbConnectorCode());
        return $db;
    }

    abstract public static function getDbConnectorCode(): string;

    abstract public static function getCacheConnectorCode(): ?string;

    /**
     * @param array $bind
     * @return array
     * @throws LazyAssertionException
     */
    final public function validateBind(array $bind): array
    {
        $newBind = [];
        $assert = Assert::lazy();
        $columns = self::getTableSchema()->getColumns();
        $columnsNames = array_keys($columns);
        foreach ($bind as $key => $value) {
            if (($key == 'id' && $value === null) || strpos($key, 'cached:') === 0) {
                continue;
            }
            if (isset($columns[$key])) {
                $column = $columns[$key];
                $value = $value !== null ? $column->convertUserValue($value) : null;
                $column->validate($assert, $value);
                $this->validateField($column->getName(), $value, $assert);
                $newBind[$column->getName()] = $value;
            } else {
                $assert->that($key, $key)->inArray($columnsNames, "unknown key $key");
            }
        }

        $assert->verifyNow();
        return $newBind;
    }

    public function getEntityDefinition(): array
    {
        return [
           $this->cacheAll()
        ];
    }

    public function getUpdateBind(): array
    {
        if (!$this->changes) {
            return [];
        }
        $changesDb = $this->changes;
        $validateData = array_replace($this->localData, $changesDb);
        //remove fields without db columns
        if ($this->hasCacheOnlyFields) {
            foreach (array_keys($changesDb) as $key) {
                if (substr($key, 0, 7) === 'cached:') {
                    unset($changesDb[$key], $validateData[$key]);
                }
            }
        }
        $this->validateData($validateData);
        return $changesDb;
    }

    protected function validateField(string $field, $value, LazyAssertion $assert)
    {
    }

    protected static function cacheAll(): bool
    {
        return false;
    }

    protected function getEntityService(string $key, string $class): Service
    {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        }
        return $this->services[$key] = new $class($this);
    }


    /**
     * @param string $entityIdFieldName
     * @return mixed|null
     */
    protected function getOneToOneEntity(string $entityIdFieldName): ?Entity
    {
        $id = $this->getField($entityIdFieldName);
        if (!$id) {
            return null;
        }
        /**
         * @var BaseReferenceIntColumn $column
         */
        $column = self::getTableSchema()->getColumns()[$entityIdFieldName];
        /**
         * @var Entity $class
         */
        $class = $column->getParentEntityClass();
        return $class::getById($id);
    }

    /**
     * @return EntityCacheIndex[]
     */
    public static function getCacheIndices(): array
    {
        return [];
    }

    protected static function getCacheFieldsProviderClass(): ?string
    {
        return null;
    }

    /**
     * @param string $entityIdFieldName
     * @return $this[]
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    protected function getOneToManyEntities(string $entityIdFieldName): array
    {
        $ids = $this->getOneToManyEntitiesIDs($entityIdFieldName);
        if (!$ids) {
            return $ids;
        }
        /**
         * @var ReferenceJsonColumn $column
         */
        $column = self::getTableSchema()->getColumns()[$entityIdFieldName];
        /**
         * @var Entity $class
         */
        $class = $column->getParentEntityClass();
        return $class::getByIds($ids);
    }

    /**
     * @param string $entityIdFieldName
     * @return int[]
     */
    protected function getOneToManyEntitiesIDs(string $entityIdFieldName): array
    {
        $ids = $this->mapToArrayFromJson($entityIdFieldName);
        return $ids ?? [];
    }

    /**
     * @param string $entityIdFieldName
     * @param array $ids
     * @return Entity|mixed
     */
    protected function setOneToManyEntitiesIDs(string $entityIdFieldName, array $ids): self
    {
        return $this->mapFromArrayToJson($entityIdFieldName, $ids ? $ids : null);
    }

    private function initById(int $id): self
    {
        $cacheKey = null;
        $this->changes = [];

        //get entity from cache (if cached)
        if ($cache = self::getCacheService()) {
            $cacheKey = $this->getCacheKey($id, $this->getDbConnectorCode(), $this->getTableName());
            $this->dbData = $this->localData = (array) $cache->get($cacheKey);
            if ($this->dbData) {
                $this->isLoadedFromCache = true;
                $this->addToLocalCache($this);
                return $this;
            }
        }

        //get entity from db
        $this->dbData = $this->localData = $this->getDbConnector()->selectRow(
            $this->getTableName(),
            new Expression('id = ?', [$id])
        );
        if (!$this->dbData) {
            throw new EntityNotFoundException(get_called_class(), $id);
        }

        //cache db fields
        if ($cache) {
            if ($this->hasCacheOnlyFields) {
                $providerClass = static::getCacheFieldsProviderClass();
                /** @var EntityCacheFieldsProvider $provider */
                $provider = new $providerClass();
                foreach ($provider->getCachedFields($this) as $key => $value) {
                    $this->localData['cached:' . $key] = $value;
                }
            }
            $cache->set($cacheKey, $this->localData, self::CACHE_TTL);
            $this->isLoadedFromCache = true;
        }
        $this->addToLocalCache($this);
        return $this;
    }

    private static function addToLocalCache(Entity $entity)
    {
        $key = get_called_class() . ':' . $entity->getId();
        self::$cache[$key] = $entity;
        if (self::$counter == 10000) {
            array_shift(self::$cache);
        } else {
            self::$counter++;
        }
    }

    private static function addToLocalCacheIfNeeded(array $row): self
    {
        $key = get_called_class() . ':' . $row['id'];
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $entity = new static($row);
        self::addToLocalCache($entity);
        return $entity;
    }

    private function validateData(array $bind)
    {
        $columns = self::getTableSchema()->getColumns();
        foreach ($columns as $key => $column) {
            if ($key == 'id') {
                continue;
            }
            if (!isset($bind[$key])) {
                $bind[$key] = $column->getDefault();
            }
        }
        $this->validateBind($bind);
    }

    private static function getCacheKey(int $id, string $connectionCode, string $tableName): string
    {
        return 'entity:' . $connectionCode . ':' . $tableName . ':{' . $id . '}';
    }

    /**
     * @return EntityCacheIndex[]
     */
    private static function getCacheIndicesInstances(): array
    {
        static $indices = [];
        $key = get_called_class();
        if (!isset($indices[$key])) {
            $indices[$key] = static::getCacheIndices();
        }
        return $indices[$key];
    }

    private static function getCacheIndex(string $code, bool $unique): EntityCacheIndex
    {
        $indices = self::getCacheIndicesInstances();
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

    private static function getCacheIndexValues(EntityCacheIndex $index, array $values): array
    {
        $fields = $index->getFields();
        if (count($fields) != count($values)) {
            throw new \InvalidArgumentException("Invalid values count in index");
        }
        return array_combine($fields, $values);
    }

    private static function getCacheKeyForIndex(string $indexCode, array $fields): string
    {
        foreach ($fields as &$field) {
            if (is_bool($field)) {
                $field = $field ? 1 : 0;
            } elseif (is_null($fields)) {
                $field = '_null_';
            }
        }
        $cacheKey = 'entity:index:' . $indexCode . ':'
            . static::getDbConnectorCode() . ':' . self::getTableName();
        return $cacheKey . ':' . md5(print_r($fields, true));
    }

    private static function getCacheKeyPartForSort(array $sort): string
    {
        return substr(md5(strtolower(print_r($sort, true))), 0, 5);
    }

    private static function getIdsFromIndex(
        string $indexCode,
        bool $many,
        array $values,
        bool $initialize,
        ?Pager $pager,
        ?array $orderFields
    ): array {
        $index = self::getCacheIndex($indexCode, !$many);
        $fields = self::getCacheIndexValues($index, $values);

        $cache = self::getCacheService();
        $ids = null;

        $cacheKey = self::getCacheKeyForIndex($indexCode, $fields);
        if ($orderFields !== null) {
            $cacheKey .= ':' . self::getCacheKeyPartForSort($orderFields);
        }
        $checkedKeys = [];
        if ($pager) {
            $minRow = ($pager->getCurrentPage() - 1) * $pager->getRowsOnPage() + 1;
            $minCacheRow = $index->getMaxCachePages() * self::ROWS_IN_CACHED_PAGE;
            if ($minRow > $minCacheRow) {
                throw new \InvalidArgumentException("Cache page $minRow is too high. Current max is " . $minCacheRow);
            }
            $ids = [];
            $firstRow = ($pager->getCurrentPage() - 1) * $pager->getRowsOnPage();
            $lastRow = $firstRow + $pager->getRowsOnPage();
            $currentPage = $firstPage = (int)floor($firstRow / self::ROWS_IN_CACHED_PAGE) + 1;
            $lastPage = (int)ceil($lastRow / self::ROWS_IN_CACHED_PAGE);

            while ($currentPage <= $lastPage) {
                $checkedKeys[] = $cacheKeyWithPage = $cacheKey . ':' . $currentPage;
                $allIds = $cache->get($cacheKeyWithPage);
                $cachePager = null;
                if ($allIds === null) {
                    $cachePager = new Pager($currentPage, self::ROWS_IN_CACHED_PAGE);
                    $allIds = self::searchIdsForIndex($index, $values, $fields, static::getDbConnector(), $cachePager, $orderFields);
                    $allIds = array_merge([$cachePager->getTotalRows()], $allIds);
                    $cache->set(
                        $cacheKeyWithPage,
                        $allIds,
                        self::CACHE_TTL
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
                $ids = self::searchIdsForIndex($index, $values, $fields, static::getDbConnector(), null, $orderFields);
                $ids = $ids ? $ids[0] : 0;
                $cache->set(
                    $cacheKey,
                    $ids,
                    self::CACHE_TTL
                );
            }
        }

        if ($ids === null) {
            $ids = self::searchIdsForIndex($index, $values, $fields, static::getDbConnector(), null, $orderFields);
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
        try {
            return $many ? self::getByIds((array) $ids) : [self::getById($ids)];
        } catch (EntityNotFoundException $e) {
            foreach ($checkedKeys as $checkedKey) {
                $cache->delete($checkedKey);
            }
            /** @var ErrorLogService $errorLog */
            $errorLog = Supernova::getContainer()->get(ErrorLogService::class);
            $errorLog->logException($e, true);
            return self::getIdsFromIndex($indexCode, $many, $values, $initialize, $pager, $orderFields);
        }
    }

    private static function searchIdsForIndex(
        EntityCacheIndex $index,
        array $values,
        array $fields,
        MysqlService $dbConnector,
        ?Pager $pager,
        ?array $orderFields
    ): array {
        $where = '1';
        $bind = [];
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
        $ids = $dbConnector->selectColumn(
            self::getTableName(),
            'id',
            new Expression($where, $bind),
            $pager
        );
        return $ids;
    }

    function __toString() // phpcs:ignore
    {
        return get_called_class() . ' with ID ' . $this->getId();
    }

    function __debugInfo() // phpcs:ignore
    {
        return array_merge([$this->__toString()], $this->localData);
    }
}
