<?php
namespace MyHammer\Library\Event\Entity;

use MyHammer\Library\Entity\Entity;
use Symfony\Component\EventDispatcher\Event;

abstract class EntityEvent extends Event
{
    private $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }
}
