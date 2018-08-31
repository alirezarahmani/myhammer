<?php
namespace MyHammer\Library\Entity;

use MyHammer\Library\Entity\Schema\TableSchema;
use Digikala\Supernova\Service\CacheService;
use Digikala\Supernova\Service\MysqlService;
use Digikala\Supernova\Services;

abstract class Reference extends Sub implements DirtyInterface
{
    private $dbConnector;
    private $cacheConnector;
    private $cacheTtl;
    private $codeName;
    private $clear = false;

    use Services;

    public function __construct(
        Entity $parentEntity,
        string $codeName,
        MysqlService $dbConnector,
        CacheService $cacheConnector,
        int $cacheTtl
    ) {
        parent::__construct($parentEntity, []);
        $this->codeName = $codeName;
        $this->dbConnector = $dbConnector;
        $this->cacheConnector = $cacheConnector;
        $this->cacheTtl = $cacheTtl;
    }

    public function getCodeName(): string
    {
        return $this->codeName;
    }

    final public function getTableName(): string
    {
        static $tableNames = [];
        $class = get_called_class();
        if (!isset($tableNames[$class])) {
            $tableSchema = static::getTableSchemaDefinition();
            $tableNames[$class] = $tableSchema->getTableName();
        }
        return $tableNames[$class];
    }

    final public static function getTableSchema(): TableSchema
    {
        static $tableSchemas = [];
        $class = get_called_class();
        if (!isset($tableSchemas[$class])) {
            $tableSchemas[$class] = static::getTableSchemaDefinition();
        }
        return $tableSchemas[$class];
    }

    public function clear()
    {
        $this->clear = true;
    }

    public function isMarkToClear(): bool
    {
        return $this->clear;
    }

    abstract protected static function getTableSchemaDefinition(): TableSchema;

    protected function getCacheConnector(): CacheService
    {
        return $this->cacheConnector;
    }

    public function getDbConnector(): MysqlService
    {
        return $this->dbConnector;
    }

    protected function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    function __toString() // phpcs:ignore
    {
        return "Reference " . $this->getCodeName() . ' for ' . $this->getEntity()->__toString();
    }

    function __debugInfo() // phpcs:ignore
    {
        return [
           $this->__toString()
        ];
    }
}
