<?php
namespace MyHammer\Library\Event;

use Symfony\Component\EventDispatcher\Event;

class ControllerAttributeEvent extends Event
{
    private $providers = [];

    public function addProvider(string $attributeClass, string $attributeProviderClass) : self
    {
        $this->providers[$attributeClass] = $attributeProviderClass;
        return $this;
    }

    public function getProviders() : array
    {
        return $this->providers;
    }
}
