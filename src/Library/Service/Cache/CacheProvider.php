<?php
namespace MyHammer\Library\Service\Cache;

use MyHammer\Domain\Model\Entity\EntityModel;
use MyHammer\Library\Service\CacheService;

class CacheProvider
{
    public function getShared() : CacheService
    {
        return $this->getService(EntityModel::MY_HAMMER_SHARED);
    }

    public function getLocal() : CacheService
    {
        return $this->getService(EntityModel::MY_HAMMER_LOCAL);
    }
}
