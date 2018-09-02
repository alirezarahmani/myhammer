<?php
namespace MyHammer\Library\Entity;

use MyHammer\Library\Entity;
use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Service\MysqlService;
use MyHammer\Library\Services;

class EntityFixer
{
    use Services;

    private $mysql;
    private $schema;

    public function __construct(MysqlService $myqsl, Entity\Schema\TableSchema $schema)
    {
        $this->mysql = $myqsl;
        $this->schema = $schema;
    }

    public function fixInsert(\PDOException $e, array $bind)
    {
        $columns = $this->schema->getColumns();
        $fixed = false;
        foreach ($bind as $key => $value) {
            if (!isset($columns[$key])) {
                unset($bind[$key]);
                $fixed = true;
                $this->serviceErrorLog()->logException(
                    'Skipped missing column ' . $key . ' in ' . $this->schema->getTableName()
                );
                continue;
            }
        }
        if ($fixed) {
            $this->mysql->insert($this->schema->getTableName(), $bind, false);
        } else {
            throw $e;
        }
    }

    public function fixUpdate(\PDOException $e, array $bind, Expression $where)
    {
        $columns = $this->schema->getColumns();
        $fixed = false;
        foreach ($bind as $key => $value) {
            if (!isset($columns[$key])) {
                unset($bind[$key]);
                $fixed = true;
                $this->serviceErrorLog()->logMessage(
                    'Skipped missing column ' . $key . ' in ' . $this->schema->getTableName()
                );
                continue;
            }
        }
        if ($fixed) {
            $bind && $this->mysql->update($this->schema->getTableName(), $bind, $where);
        } else {
            throw $e;
        }
    }
}
