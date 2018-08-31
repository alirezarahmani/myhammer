<?php
namespace MyHammer\Library\Entity\Exception;

use MyHammer\Library\Entity\Entity;

class EntityMutexException extends \Exception
{

    public function __construct(Entity $entity, string $identifier)
    {
        $name = get_class($entity);
        $message = "Entity $name:$identifier mutex found";
        parent::__construct($message);
    }
}
