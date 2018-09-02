<?php
namespace MyHammer\Library\Service\Mysql;

use Throwable;

class UniqueKeyException extends \PDOException
{
    private $indexName;

    private $value;

    public function __construct(string $message, string $indexName, string $value, Throwable $previous = null)
    {
        parent::__construct($message, 23000, $previous);
        $this->indexName = $indexName;
        $this->value = $value;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
