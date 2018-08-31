<?php
namespace MyHammer\Library\Entity\Schema;

use MyHammer\Library\Exception\InvalidArgumentException;
use Digikala\Supernova\Service\TimeService;
use Digikala\Supernova\Services;

class TableSchema
{
    use Services;

    private $tableName;
    private $columns;
    private $finalColumns;
    private $defaults;

    public function __construct(string $tableName, ColumnSchema ...$columns)
    {
        $this->tableName = $tableName;
        $this->columns = $columns;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $name)
    {
        $this->tableName = $name;
    }

    public function getDefaults(string $connectionUri): array
    {
        if ($this->defaults === null) {
            $defaults = $this->serviceCache()->getLocal()->getWithClosure(
                'entity:table:defaults:' . $this->getTableName() . $connectionUri,
                TimeService::YEAR,
                function () {
                    $defaults = [];
                    foreach ($this->getFinalColumns() as $column) {
                        if ($column->getName() == 'id') {
                            continue;
                        }
                        if (($default = $column->getDefault()) !== null) {
                            $defaults[$column->getName()] = $default;
                        }
                    }
                    return $defaults;
                }
            );
            $this->defaults = $defaults;
        }
        return $this->defaults;
    }

    /**
     * @return ColumnSchema[]
     */
    public function getColumns(): array
    {
        return $this->getFinalColumns();
    }

    public function getCreateTableSql(): string
    {
        $sql = "CREATE TABLE `{$this->getTableName()}` (\n";
        foreach ($this->getFinalColumns() as $column) {
            $columnSql = $column->getColumnDefinitionSql();
            $sql .= "  $columnSql,\n";
        }
        rtrim($sql, ",\n");

        foreach ($this->getIndexes() as $index) {
            $sql .= "  $index,\n";
        }
        $sql = rtrim($sql, "\n,");
        $sql .= "\n) " . $this->getEngineDefinition() . ';';
        return $sql;
    }

    public function getEngineDefinition(): string
    {
        return 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    public function getIndexColumns(string $indexName): array
    {
        $indexes = [];
        foreach ($this->getFinalColumns() as $column) {
            $indexes = array_replace_recursive($indexes, $column->getIndexes());
        }
        $index = array_pop($indexes[$indexName]);
        $columns = [];
        foreach ($index as $row) {
            $columns[] = $row[0];
        }
        return $columns;
    }

    public function getIndexes(): array
    {
        $indexes = [];
        foreach ($this->getFinalColumns() as $column) {
            $indexes = array_replace_recursive($indexes, $column->getIndexes());
        }
        $return = [];
        foreach (['PRIMARY KEY', 'UNIQUE KEY', 'KEY'] as $order) {
            foreach ($indexes as $indexName => $level1) {
                $sql = '';
                foreach ($level1 as $type => $definition) {
                    $indexCode = $indexName == 'PRIMARY' ? 'PRIMARY KEY' : $type;
                    if ($indexCode != $order) {
                        continue(2);
                    }
                    if ($indexName != 'PRIMARY') {
                        $sql .= $indexCode . " `$indexName`";
                    } else {
                        $sql .= $indexCode;
                    }
                    $sql .= ' (';
                    ksort($definition);
                    foreach ($definition as $indexParams) {
                        $sql .= "`{$indexParams[0]}";
                        if ($indexParams[1]) {
                            $sql .= "({$indexParams[1]})";
                        }
                        $sql .= '`,';
                    }
                    $sql = rtrim($sql, ",");
                    $sql .= "),\n";
                }
                $return[$indexName] = rtrim($sql, ",\n");
            }
        }
        return $return;
    }

    /**
     * @return ColumnSchema[]
     */
    private function getFinalColumns(): array
    {
        if ($this->finalColumns === null) {
            $this->finalColumns = $this->createColumns();
        }
        return $this->finalColumns;
    }

    /**
     * @return ColumnSchema[]
     */
    private function createColumns(): array
    {
        $return = [];
        $columns = $this->columns;
        $iterations = 0;
        while (true) {
            $hasDots = false;
            foreach ($columns as $column) {
                $name = $column->getName();
                if (strpos($name, '..')) {
                    $hasDots = true;
                    preg_match_all('/\[[0-9]*..[0-9]*\]/', $name, $found);
                    if (!isset($found[0][0])) {
                        throw new InvalidArgumentException('Invalid column definition ' . $name);
                    }
                    $exploded = explode('..', trim($found[0][0], '[]'));
                    if (count($exploded) != 2) {
                        throw new InvalidArgumentException('Invalid column definition ' . $name);
                    }
                    if ($exploded[0] > $exploded[1]) {
                        throw new InvalidArgumentException('Invalid column definition ' . $name);
                    }
                    for ($i = $exploded[0]; $i <= $exploded[1]; $i++) {
                        if (++$iterations == 1000) {
                            throw new InvalidArgumentException("Wrong column in table " . $this->getTableName() . " for column " . $name);
                        }
                        $newColumn = clone $column;
                        $pos = strpos($name, $found[0][0]);
                        $newColumn->setName(substr_replace($name, $i, $pos, strlen($found[0][0])));
                        $return[$newColumn->getName()] = $newColumn;
                    }
                } else {
                    $return[$column->getName()] = $column;
                }
            }
            if (!$hasDots) {
                break;
            }
            $columns = $return;
            $return = [];
        }

        $columns = $return;
        $return = [];
        foreach ($columns as $column) {
            $name = $column->getName();
            if (strpos($name, '[lang]')) {
                $languages = $this->serviceLocale()->getAvailableLanguages();
                foreach ($languages as $lang) {
                    $newColumn = clone $column;
                    $newColumn->setName(str_replace('[lang]', $lang, $name));
                    if ($lang != $this->serviceLocale()->getDefaultLanguage()) {
                        $newColumn->allowNull(true);
                    }
                    $return[$newColumn->getName()] = $newColumn;
                }
            } elseif (($idx = strpos($name, '[CSV:')) !== false) {
                $base = substr($name, 0, $idx);
                $enums = substr(str_replace(']', '', $name), $idx + 5);
                foreach (explode(',', $enums) as $suffix) {
                    $newColumn = clone $column;
                    $newColumn->setName($base . $suffix);
                    $return[$newColumn->getName()] = $newColumn;
                }
            } else {
                $return[$column->getName()] = $column;
            }
        }

        return $return;
    }
}
