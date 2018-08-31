<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Library\Entity\Entity;

abstract class EntityModel extends Entity
{

    public static function getDbConnectorCode(): string
    {
        return 'mysql.myHammer.model';
    }
}
