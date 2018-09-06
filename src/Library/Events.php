<?php
namespace MyHammer\Library;

final class Events
{

    /**
     * Run when entity was added to database
     *
     * @event myhammer\Event\Entity\EntityAddedEvent
     */
    const ENTITY_ADDED = 'myhammer.entity.added';

    /**
     * Run when entity was deleted from database
     *
     * @event myhammer\Event\Entity\EntityDeletedEvent
     */
    const ENTITY_DELETED = 'myhammer.entity.deleted';

    /**
     * Run when entity was updated in database
     *
     * @event myhammer\Event\Entity\EntityUpdatedEvent
     */
    const ENTITY_UPDATED = 'myhammer.entity.updated';

    /**
     * Run when entity is going to be added to database
     *
     * @event myhammer\Event\Entity\EntityAddingEvent
     */
    const ENTITY_ADDING = 'myhammer.entity.adding';

    /**
     * Run when entity is going to be deleted from database
     *
     * @event myhammer\Event\Entity\EntityDeletingEvent
     */
    const ENTITY_DELETING = 'myhammer.entity.deleting';

    /**
     * Run when entity is going to be  updated in database
     *
     * @event myhammer\Event\Entity\EntityUpdatingEvent
     */
    const ENTITY_UPDATING = 'myhammer.entity.updating';
}
