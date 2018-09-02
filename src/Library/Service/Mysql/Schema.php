<?php
namespace MyHammer\Library\Service\Mysql;

use MyHammer\Library\Lib\Entity\Schema\ColumnSchema;
use MyHammer\Library\Service\MysqlService;
use MyHammer\Library\Service\TimeService;
use MyHammer\Library\Services;

class Schema
{
    use Services;

    private $mysql;

    public function __construct(MysqlService $mysql)
    {
        $this->mysql = $mysql;
    }

    /**
     * @param string $tableName
     * @param ColumnSchema[] $columns
     * @param string $engine
     */
    public function addTempTable(string $tableName, array $columns, string $engine = 'MEMORY')
    {
        $this->mysql->query("DROP TABLE IF EXISTS $tableName");
        $sql = "CREATE TABLE `$tableName`(";
        foreach ($columns as $column) {
            $sql .= $column->getColumnDefinitionSql() . ',';
        }
        $sql = rtrim($sql, ',') . ') ENGINE ' . $engine;
        $this->mysql->query($sql);
    }

    public function dropTempTable(string $tableName)
    {
        $this->mysql->query("DROP TABLE IF EXISTS `$tableName`");
    }

    public function addEnumIfMissing(string $tableName, string $column, string $value)
    {
        static $enumCache;
        if (isset($enumCache[$tableName][$column][$value])) {
            return;
        }
        $cacheKey = 'has:enum:' . $tableName . ':' . $column . ':' . $value;
        $cacheService = $this->serviceCache()->getLocal();
        if ($cacheService->get($cacheKey)) {
            return;
        }
        if ($enumCache === null) {
            $enumCache = [];
        }
        if (!isset($enumCache[$tableName])) {
            $enumCache[$tableName] = [];
        }
        if (!isset($enumCache[$tableName][$column])) {
            $enumCache[$tableName][$column] = [];
        }
        $enumCache[$tableName][$column][$value] = 1;

        $definition = $this->mysql->selectCustomRow(
            new Expression("SHOW columns FROM $tableName WHERE field = ?", [$column])
        );
        $default = $definition['Default'] === null ? 'NULL' : ("'" .  $definition['Default']  . "'");
        $notNull = $definition['Null'] == 'NO' ? 'NOT NULL' : '';
        $definition = explode("','", substr(substr($definition['Type'], 5), 0, -1));
        foreach ($definition as &$val) {
            $val = trim($val, "'");
        }
        if (!in_array($value, $definition)) {
            $definition[] = $value;
            foreach ($definition as &$val) {
                $val = "'$val'";
            }
            $values = implode(',', $definition);
            $sql = "ALTER TABLE $tableName CHANGE COLUMN $column {$column} enum($values) $notNull DEFAULT $default";
            $this->mysql->query($sql, []);
        }
        $cacheService->set($cacheKey, 1, TimeService::WEEK);
    }
}
