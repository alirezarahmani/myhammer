<?php
namespace MyHammer\Library\Event;

use Digikala\Supernova\Service\MysqlService;
use Symfony\Component\EventDispatcher\Event;

class HealthCheckEvent extends Event
{
    private $checks = [];
    private $mysql = [];

    public function addCheck(string $code, \Closure $check) : self
    {
        $this->checks[$code] = $check;
        return $this;
    }

    public function addMysql(string $code, MysqlService $mysql) : self
    {
        $this->mysql[$code] = $mysql;
        return $this;
    }

    public function getChecks() : array
    {
        return $this->checks;
    }

    /**
     * @return MysqlService[]
     */
    public function getMysql() : array
    {
        return $this->mysql;
    }
}
