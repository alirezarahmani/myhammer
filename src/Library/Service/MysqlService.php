<?php
namespace MyHammer\Application\Service;

use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Service\Mysql\ILogger;
use MyHammer\Library\Service\Mysql\Pager;
use MyHammer\Library\Service\Mysql\Schema;
use MyHammer\Library\Service\Mysql\UniqueKeyException;

class MysqlService
{

    protected $pdo;
    protected $uri;
    protected $settings;

    private $schema;
    private $connectionCode;
    /**
     * @var ILogger
     */
    private $logger;
    private $transactionCounter = 0;
    private $logInserts = 0;
    private $logUpdates = 0;
    private $logDeletes = 0;
    private $logSelects = 0;


    public function __construct(string $connectionCode = 'default')
    {
        $this->settings = $this->serviceSettings()['mysql'][$connectionCode];
        $this->connectionCode = $connectionCode;
    }

    public function getSchema() : Schema
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this);
        }
        return $this->schema;
    }

    public function getConnectionCode(): string
    {
        return $this->connectionCode;
    }

    public function getDatabaseName(): string
    {
        $uri = $this->getConnectionUri();
        $dbName = substr($uri, strpos($uri, ';dbname=') + 8);
        if ($pos = strpos($dbName, ';')) {
            $dbName = substr($dbName, 0, $pos);
        }
        if ($threadId = $this->getThreadId()) {
            $dbName .= '_' . $threadId;
        }
        return $dbName;
    }

    public function setLogger(ILogger $logger)
    {
        $this->logger = $logger;
    }

    //TODO @Krassi remove after migration
    /**
     * @param string $table
     * @return int
     * @deprecated
     */
    public function count(string $table): int
    {
        $statement = $this->getStatement('SELECT COUNT(*) FROM ' . $table, [], false);
        $result = $statement[0]->fetch(\PDO::FETCH_ASSOC);
        $statement[0]->closeCursor();

        return array_shift($result);
    }

    public function query($sql, $bind = [], bool $log = true): int
    {
        $this->logSelects++;
        $statement = $this->getStatement($sql, $bind, true, $log);
        $statement[0]->closeCursor();
        return $statement[2];
    }

    public function quote($value, $parameterType = \PDO::PARAM_STR)
    {
        return $this->getConnection()->quote($value, $parameterType);
    }

    public function iterateRows(string $table, Expression $expression, array $fields = ['*'])
    {
        $this->logSelects++;
        $sql = $this->createSelectQuery(
            $table,
            $fields,
            $expression->getQueryPart()
        );
        $pdo = $this->getConnection();
        $statement = $pdo->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
        $statement->execute($expression->getBind());
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            yield $row;
        }
    }

    public function selectRows(
        $table,
        Expression $whereCondition,
        Pager $pager = null,
        $fields = ['*'],
        bool $calcFoundRows = true,
        string $join = null
    ): array {
        $this->logSelects++;
        $calcFoundRows = $pager != null && $calcFoundRows;
        $sql = $this->createSelectQuery(
            $table,
            $fields,
            $whereCondition->getQueryPart(),
            $pager,
            $calcFoundRows ? 'SQL_CALC_FOUND_ROWS' : '',
            $join
        );
        $result = $this->select($sql, $whereCondition->getBind(), 'fetchAll', \PDO::FETCH_ASSOC, $calcFoundRows);
        if ($calcFoundRows) {
            $pager->setTotalRows($result[1]);
            return $result[0];
        }
        return $result;
    }

    public function selectRow($table, Expression $whereCondition, $fields = ['*'], string $join = null): array
    {
        $this->logSelects++;
        $sql = $this->createSelectQuery($table, $fields, $whereCondition->getQueryPart(), new Pager(1, 1), null, $join);
        return $this->select($sql, $whereCondition->getBind(), 'fetch', \PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param Expression $query
     * @param Pager|null $pager
     * @return array|mixed
     */
    public function selectCustomRows(Expression $query, Pager $pager = null)
    {
        $this->logSelects++;
        $sql = $query->getQueryPart();
        if ($pager != null) {
            $sql .= ' LIMIT ' . ($pager->getCurrentPage() - 1) * $pager->getRowsOnPage() . ',' . $pager->getRowsOnPage();
        }
        $result = $this->select($sql, $query->getBind(), 'fetchAll', \PDO::FETCH_ASSOC, $pager != null);
        if ($pager != null) {
            $pager->setTotalRows($result[1]);
            return $result[0];
        }
        return $result;
    }

    /**
     * @param Expression $query
     * @return array
     */
    public function selectCustomColumn(Expression $query)
    {
        $this->logSelects++;
        return $this->select(
            $query->getQueryPart(),
            $query->getBind(),
            'fetchAll',
            \PDO::FETCH_ASSOC | \PDO::FETCH_COLUMN
        );
    }

    /**
     * @param Expression $query
     * @return array
     */
    public function selectCustomRow(Expression $query)
    {
        $this->logSelects++;
        return $this->select($query->getQueryPart(), $query->getBind(), 'fetch', \PDO::FETCH_ASSOC);
    }

    /**
     * @param Expression $query
     * @return array
     */
    public function selectCustomField(Expression $query)
    {
        $this->logSelects++;
        return $this->select($query->getQueryPart(), $query->getBind(), 'fetch', \PDO::FETCH_COLUMN);
    }

    public function getLock(string $lockName, int $timeout): bool
    {
        $this->logSelects++;
        return $this->select(
            "SELECT GET_LOCK(?, $timeout)",
            [$lockName],
            'fetch',
            \PDO::FETCH_COLUMN
        ) ? true : false;
    }

    public function releaseLock(string $lockName): bool
    {
        $this->logSelects++;
        return $this->select(
            "SELECT RELEASE_LOCK(?)",
            [$lockName],
            'fetch',
            \PDO::FETCH_COLUMN
        ) ? true : false;
    }

    public function selectColumn(
        $table,
        $field,
        Expression $whereCondition,
        Pager $pager = null,
        bool $calcFoundRows = true,
        string $join = null
    ) {
        $this->logSelects++;
        $calcFoundRows = $pager != null && $calcFoundRows;
        $sql = $this->createSelectQuery(
            $table,
            [$field],
            $whereCondition->getQueryPart(),
            $pager,
            $calcFoundRows ?'SQL_CALC_FOUND_ROWS' : '',
            $join
        );
        $result = $this->select($sql, $whereCondition->getBind(), 'fetchAll', \PDO::FETCH_COLUMN, $calcFoundRows);
        if ($calcFoundRows) {
            $pager->setTotalRows($result[1]);
            return $result[0];
        } else {
            return $result;
        }
    }

    public function selectField($table, $field, Expression $whereCondition)
    {
        $this->logSelects++;
        $sql = $this->createSelectQuery($table, [$field], $whereCondition->getQueryPart(), new Pager(1, 1));
        return $this->select($sql, $whereCondition->getBind(), 'fetchColumn', 0);
    }

    public function insert(string $table, array $bind, bool $returnPrimaryKey = true, Expression $onDuplicateKey = null): ?int
    {
        $this->logInserts++;
        return $this->buildInsertSql('INSERT', $table, [$bind], $returnPrimaryKey, $onDuplicateKey);
    }

    public function insertMany(string $table, array $bind, bool $returnPrimaryKey = false, Expression $onDuplicateKey = null): ?int
    {
        $this->logInserts++;
        return $this->buildInsertSql('INSERT', $table, $bind, $returnPrimaryKey, $onDuplicateKey);
    }

    public function replace(string $table, array $bind, bool $returnPrimaryKey = true, Expression $onDuplicateKey = null)
    {
        $this->logInserts++;
        return $this->buildInsertSql('REPLACE', $table, [$bind], $returnPrimaryKey, $onDuplicateKey);
    }

    public function update(string $table, array $bind, Expression $whereCondition)
    {
        $this->logUpdates;
        $values = [];
        $queryBind = [];
        foreach ($bind as $column => $value) {
            $key =  '`' . $column . '` = ';
            if ($value instanceof Expression) {
                $key .= $value->getQueryPart();
                $queryBind = array_merge($queryBind, $value->getBind());
            } else {
                $key .= '?';
                $queryBind[] = $this->mapBindValue($value);
            }
            $values[] = $key;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $values) . ' WHERE ' . $whereCondition->getQueryPart();
        $queryBind = array_merge($queryBind, $whereCondition->getBind());
        $statement = $this->getStatement($sql, array_values($queryBind), true);
        $statement[0]->closeCursor();
        return $statement[2];
    }

    public function delete($table, Expression $whereCondition)
    {
        $this->logDeletes++;
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $whereCondition->getQueryPart();
        $statement = $this->getStatement($sql, $whereCondition->getBind(), true);
        $statement[0]->closeCursor();
        return $statement[2];
    }

    public function formatDateTime(int $timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function getDeleteQueriesCount(): int
    {
        return $this->logDeletes;
    }

    public function getInsertQueriesCount(): int
    {
        return $this->logInserts;
    }

    public function getUpdateQueriesCount(): int
    {
        return $this->logUpdates;
    }

    public function getSelectQueriesCount(): int
    {
        return $this->logSelects;
    }

    public function beginTransaction()
    {
        if (!$this->transactionCounter) {
            $this->getConnection()->beginTransaction();
        }
        $this->transactionCounter++;
    }

    public function commit()
    {
        $this->transactionCounter--;
        if (!$this->transactionCounter) {
            $this->getConnection()->commit();
        } elseif ($this->transactionCounter < 0) {
            throw new \InvalidArgumentException('Too many commits for db transactions');
        }
    }

    public function rollback()
    {
        if ($this->transactionCounter === 1) {
            $this->getConnection()->rollBack();
            $this->transactionCounter = 0;
        } elseif ($this->transactionCounter > 1) {
            throw new \InvalidArgumentException('Rollback in nested transaction is not allowed');
        }
    }

    public function closeConnection()
    {
        $this->pdo = null;
    }

    public function buildInsertSql(
        string $operation,
        string $table,
        array $binds,
        bool $returnPrimaryKey = true,
        Expression $onDuplicateKey = null
    ): ?int {
        $keys = [];
        foreach ($binds as $bind) {
            $keys = array_replace($keys, array_keys($bind));
        }
        $finalBinds = $values =  [];
        foreach ($binds as $bind) {
            $newValues = [];
            foreach ($keys as $key) {
                if (isset($bind[$key])) {
                    $newValues[] = '?';
                    $finalBinds[] = $this->mapBindValue($bind[$key]);
                } else {
                    $newValues[] = 'DEFAULT';
                }
            }
            $values[] = '(' . implode(',', $newValues) . ')';
        }
        $sql = $operation . ' INTO ' . $table;
        foreach ($keys as &$key) {
            $key = "`$key`";
        }
        $sql .= '(' . implode(',', $keys) . ') VALUES';
        $sql .= implode(',', $values);
        if ($onDuplicateKey) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $onDuplicateKey->getQueryPart();
            $finalBinds = array_merge($finalBinds, $onDuplicateKey->getBind());
        }
        $statement = $this->getStatement($sql, $finalBinds, true);
        $id = $returnPrimaryKey ? $statement[1]->lastInsertId() : null;
        $statement[0]->closeCursor();
        return $id;
    }

    public function getConnectionUri(): string
    {
        return $this->settings['uri'];
    }

    private function getStatement($sql, $bind, $returnAffectedRows = false, $log = true): array
    {
        $pdo = $this->getConnection();
        $time = microtime(true);
        $statement = $pdo->prepare($sql);
        try {
            $result = $statement->execute($bind);
            $returned = $returnAffectedRows ? $statement->rowCount() : $result;
            if ($log && $this->logger) {
                $time = microtime(true) - $time;
                $this->logger->logQuery($sql, $time);
            }
            return [$statement, $pdo, $returned];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                preg_match("/(?=.*Duplicate entry '(?<value>[^']*)')(?=.*for key '(?P<name>\w+)')/i", $e->getMessage(), $matches);
                throw new UniqueKeyException($e->getMessage(), $matches['name'] ?? '', $matches['value'] ?? '', $e);
            }
            throw new \PDOException($e->getMessage() . ". SQL: $sql BIND: ".print_r($bind, true));
        }
    }

    private function createSelectQuery($table, $fields, $where, Pager $pager = null, $modifier = null, string $join = null)
    {
        $sql = 'SELECT ' . ($modifier ? $modifier . ' ' : '') . implode(',', $fields)
            . ' FROM ' . $table . ($join ? ' '.$join : ''). ($where ? ' WHERE ' . $where : '');
        if ($pager != null) {
            $sql .= ' LIMIT ' . ($pager->getCurrentPage() - 1) * $pager->getRowsOnPage() . ',' . $pager->getRowsOnPage();
        }
        return $sql;
    }

    private function select(string $sql, array $bind, string $function, $argument, bool $getFoundRows = false)
    {
        $statement = $this->getStatement($sql, $bind, false);
        $result = $statement[0]->$function($argument);
        $statement[0]->closeCursor();
        if ($getFoundRows) {
            $statement = $this->getStatement('SELECT FOUND_ROWS()', [], false);
            $total = (int)$statement[0]->fetchColumn(0);
            $statement[0]->closeCursor();
            $result = [$result, $total];
        }
        return $result;
    }

    private function getConnection(): \PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        $uri = $this->getConnectionUri();
        if ($threadId = 1) {
            $dbName = substr($uri, strpos($uri, ';dbname=') + 8);
            if ($pos = strpos($dbName, ';')) {
                $dbName = substr($dbName, 0, $pos);
            }
            $uri = str_replace($dbName, $dbName . '_' . $threadId, $uri);
        }
        $pdo = new \PDO($uri, $this->settings['user'], $this->settings['pass']);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $pdo->setAttribute(\PDO::ATTR_CASE, 0);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
        return $pdo;
    }

    private function mapBindValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        } elseif (is_bool($value)) {
            return $value ? 1 : 0;
        } elseif (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }
}
