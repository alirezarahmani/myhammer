<?php

namespace MyHammer\Domain\Events;

use Symfony\Component\EventDispatcher\Event;

class ApiRegisterDemandEvent extends Event
{
    public const EVENT_NAME = 'api.register.demand';
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
