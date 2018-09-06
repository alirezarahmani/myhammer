<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class ClearLocalCacheEvent extends Event
{
    private $preWorks = [];
    private $postWorks = [];
    private $oldNameSpace;
    private $newNameSpace;
    private $fast;

    public function __construct(string $oldNameSpace, string $newNameSpace, bool $fast)
    {
        $this->oldNameSpace = $oldNameSpace;
        $this->newNameSpace = $newNameSpace;
        $this->fast = $fast;
    }

    public function getOldNameSpace(): string
    {
        return $this->oldNameSpace;
    }

    public function getNewNameSpace(): string
    {
        return $this->newNameSpace;
    }

    public function isFast(): bool
    {
        return $this->fast;
    }

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
