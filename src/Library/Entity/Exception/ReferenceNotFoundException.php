<?php
namespace MyHammer\Library\Entity\Exception;

use MyHammer\Library\Entity\Entity;

class ReferenceNotFoundException extends \Exception
{
    public function __construct(Entity $entity, string $identifier)
    {
        $class = get_class($entity);
        $message = "Reference $identifier not found in $class for entity ";
        $message.= $entity->getId();
        parent::__construct($message);
    }
}
