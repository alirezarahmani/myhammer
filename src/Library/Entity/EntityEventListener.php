<?php
namespace MyHammer\Library\Entity;

use MyHammer\Library\Event\Entity\EntityAddedEvent;
use MyHammer\Library\Event\Entity\EntityAddingEvent;
use MyHammer\Library\Event\Entity\EntityDeletedEvent;
use MyHammer\Library\Event\Entity\EntityDeletingEvent;
use MyHammer\Library\Event\Entity\EntityUpdatedEvent;
use MyHammer\Library\Event\Entity\EntityUpdatingEvent;
use MyHammer\Library\Events;
use MyHammer\Library\Services;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class EntityEventListener implements EventSubscriberInterface
{
    use Services;

    public function onAdding(EntityAddingEvent $event)
    {
        $this->hideWarning($event);
    }

    public function onAdded(EntityAddedEvent $event)
    {
        $this->hideWarning($event);
    }

    public function onUpdating(EntityUpdatingEvent $event)
    {
        $this->hideWarning($event);
    }

    public function onUpdated(EntityUpdatedEvent $event)
    {
        $this->hideWarning($event);
    }

    public function onDeleting(EntityDeletingEvent $event)
    {
        $this->hideWarning($event);
    }

    public function onDeleted(EntityDeletedEvent $event)
    {
        $this->hideWarning($event);
    }

    abstract public static function getActiveEvents(): array;

    final public static function getSubscribedEvents()
    {
        if (defined('DBM_COMMAND')) { //TODO remove after migration and when import scripts are running we should remove container
            return [];
        }
        $events = [];
        $mapping = [
            Events::ENTITY_ADDING => ['onAdding', 255],
            Events::ENTITY_ADDED => ['onAdded', 255],
            Events::ENTITY_UPDATING => ['onUpdating', 255],
            Events::ENTITY_UPDATED => ['onUpdated', 255],
            Events::ENTITY_DELETING => ['onDeleting', 255],
            Events::ENTITY_DELETED => ['onDeleted', 255]
        ];
        foreach (static::getActiveEvents() as $event) {
            $events[$event] = $mapping[$event];
        }
        return $events;
    }
}
