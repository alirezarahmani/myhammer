<?php
namespace MyHammer\Library\Cache\Storages;

use MyHammer\Library\Cache\CacheStorage;

class APCUCacheStorage implements CacheStorage
{

    public function set(string $key, $value, int $ttl)
    {
        apcu_store($key, $value, $ttl);
    }

    public function get(string $key)
    {
        $value = apcu_fetch($key);
        return $value === false ? null : $value;
    }

    public function gets(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function increment(string $key, int $by = 1): int
    {
        return apcu_inc($key, $by);
    }

    public function decrement(string $key, int $by = 1): int
    {
        return apcu_dec($key, $by);
    }

    public function delete(string $key)
    {
        apcu_delete($key);
    }

    public function deleteMany(array $keys)
    {
        apcu_delete($keys);
    }

    public function clear()
    {
        apcu_clear_cache();
    }

    public function iterateKeys(string $prefix = null): \Iterator
    {
        return new \APCUIterator($prefix ? ('/^' . $prefix . '/') : null);
    }

    public function getFreeMemory(): int
    {
        return apcu_sma_info(true)['avail_mem'];
    }

    public function getAvailableMemory(): int
    {
        $val =  ini_get('apc.shm_size');
        if (strpos($val, 'M')) {
            $val = ((int) $val) * 1024 * 1024;
        } elseif (strpos($val, 'G')) {
            $val = ((int) $val) * 1024 * 1024 * 1024;
        }
        return $val;
    }
}
