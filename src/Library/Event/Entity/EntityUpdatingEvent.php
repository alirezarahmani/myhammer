<?php
namespace MyHammer\Library\Event\Entity;

use MyHammer\Library\Entity\Entity;

class EntityUpdatingEvent extends EntityEvent
{
    private $bind;
    private $currentData;

    public function __construct(Entity $entity, array $bind, array $currentData)
    {
        parent::__construct($entity);
        $this->bind = $bind;
        $this->currentData = $currentData;
    }

    public function getBind(): array
    {
        return $this->bind;
    }

    public function getCurrentData(): array
    {
        return $this->currentData;
    }

    public function addToBind(string $key, $value): self
    {
        $this->bind[$key] = $value;
        return $this;
    }
}
