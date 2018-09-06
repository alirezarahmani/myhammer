<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class WarmUpEvent extends Event
{
    private $checks = [];
    private $urls = [];

    public function addCheck(string $code, \Closure $check) : self
    {
        $this->checks[$code] = $check;
        return $this;
    }

    public function getChecks() : array
    {
        return $this->checks;
    }

    public function addUrl(string $url) : self
    {
        $this->urls[$url] = 1;
        return $this;
    }

    public function getUrls() : array
    {
        return array_keys($this->urls);
    }
}
