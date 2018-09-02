<?php
namespace MyHammer\Library\Service\Mysql;

interface ILogger
{
    public function logQuery(string $query, float $time);
}
