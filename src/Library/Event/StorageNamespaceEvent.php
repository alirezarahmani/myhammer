<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class StorageNamespaceEvent extends Event
{
    private $namespaces = [];

    public function registerNamespace(int $code, string $namespace) : self
    {
        $this->namespaces[$code] = $namespace;
        return $this;
    }

    public function getNamespaces() : array
    {
        return $this->namespaces;
    }
}
