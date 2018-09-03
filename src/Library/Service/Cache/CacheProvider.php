<?php
namespace MyHammer\Library\Service\Cache;

use MyHammer\Library\Service\CacheService;

class CacheProvider
{
    public function getShared() : CacheService
    {
        return $this->getService('supernova:cache:shared');
    }

    public function getLocal() : CacheService
    {
        return $this->getService('supernova:cache:local');
    }
}
