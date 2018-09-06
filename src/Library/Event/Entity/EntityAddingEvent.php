<?php
namespace MyHammer\Library\Event\Entity;

use MyHammer\Library\Entity\Entity;

class EntityAddingEvent extends EntityEvent
{
    private $bind;

    public function __construct(Entity $entity, array $bind)
    {
        parent::__construct($entity);
        $this->bind = $bind;
    }

    public function getBind(): array
    {
        return $this->bind;
    }

    public function addToBind(string $key, $value): self
    {
        $this->bind[$key] = $value;
        return $this;
    }
}
