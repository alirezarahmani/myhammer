<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class DevControllerEvent extends Event
{
    private $links = [];

    public function addLink(string $label, string $url, string $description) : self
    {
        $this->links[$label] = [$url, $description];
        return $this;
    }

    public function getLinks() : array
    {
        return $this->links;
    }
}
