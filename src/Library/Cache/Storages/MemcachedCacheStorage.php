<?php
namespace MyHammer\Library\Cache\Storages;

use MyHammer\Library\Cache\CacheStorage;
use MyHammer\Library\Service\MemcachedService;

class MemcachedCacheStorage implements CacheStorage
{

    private $memcached;

    public function __construct(MemcachedService $memcached)
    {
        $this->memcached = $memcached;
    }

    public function set(string $key, $value, int $ttl)
    {
        $this->memcached->setExpire($key, $value, $ttl);
    }

    public function get(string $key)
    {
        return $this->memcached->get($key);
    }

    public function gets(array $keys): array
    {
        return $this->memcached->gets($keys);
    }

    public function increment(string $key, int $by = 1): int
    {
        return $this->memcached->incBy($key, $by);
    }

    public function decrement(string $key, int $by = 1): int
    {
        return $this->memcached->decBy($key, $by);
    }

    public function delete(string $key)
    {
        $this->memcached->delete($key);
    }

    public function deleteMany(array $keys)
    {
        $this->memcached->deleteMany($keys);
    }

    public function clear()
    {
        $this->memcached->flush();
    }

    public function iterateKeys(string $prefix = null): \Iterator
    {
        return null;
    }

    public function getFreeMemory(): int
    {
        return 0;
    }

    public function getAvailableMemory(): int
    {
        return 0;
    }
}
