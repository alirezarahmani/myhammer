<?php
namespace MyHammer\Domain\Model\Entity;

use MyHammer\Library\Entity\Entity;

abstract class EntityModel extends Entity
{
    const MY_HAMMER_LOCAL = 'myHammer:cache:local';
    const MY_HAMMER_SHARED = 'myHammer:cache:shared';


    public static function getDbConnectorCode(): string
    {
        return 'mysql.myHammer.model';
    }
}
