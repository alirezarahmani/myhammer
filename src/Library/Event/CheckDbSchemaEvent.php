<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class CheckDbSchemaEvent extends Event
{
    private $preWorks = [];
    private $postWorks = [];

    public function addPreWork(\Closure $work) : self
    {
        $this->preWorks[] = $work;
        return $this;
    }

    public function addPostWork(\Closure $work) : self
    {
        $this->postWorks[] = $work;
        return $this;
    }


    public function getPreWorks() : array
    {
        return $this->preWorks;
    }

    public function getPostWorks() : array
    {
        return $this->postWorks;
    }
}
