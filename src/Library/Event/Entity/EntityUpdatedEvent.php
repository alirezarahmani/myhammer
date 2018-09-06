<?php
namespace MyHammer\Library\Event\Entity;

use MyHammer\Library\Entity\Entity;

class EntityUpdatedEvent extends EntityEvent
{
    private $entityChanges;
    private $referencesChanges;

    public function __construct(Entity $entity, array $entityChanges, array $referencesChanges)
    {
        parent::__construct($entity);
        $this->entityChanges = $entityChanges;
        $this->referencesChanges = $referencesChanges;
    }

    public function getEntityChanges(): array
    {
        return $this->entityChanges;
    }

    public function getReferencesChanges(): array
    {
        return $this->referencesChanges;
    }
}
