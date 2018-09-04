<?php
namespace MyHammer\Library\Service;

use MyHammer\Library\Cache\CacheStorage;

class CacheService
{
    private $cacheStorage;
    private $threadId;
    private $namespaceCounter;
    private $mode;
    private $keyPrefix;

    public function __construct(CacheStorage $storage)
    {

        $this->cacheStorage = $storage;
        $this->threadId = 1;
        if ($this->threadId) {
            $this->namespaceCounter = (int) $storage->get('key_counter');
        }
        $this->mode = '';
        $this->keyPrefix = '';
    }

    public function set(string $key, $value, int $ttl)
    {
        $this->cacheStorage->set($this->createKey($key), $value ?? '__NULL__', $ttl);
    }

    public function getWithClosure(string $key, int $ttl, \Closure $closure)
    {
        $value = $this->get($key);
        if ($value === null) {
            $value = $closure();
            $this->set($key, $value, $ttl);
        }
        return $value;
    }

    public function get(string $key)
    {
        return $this->cacheStorage->get($this->createKey($key));
    }

    public function gets(array $keys)
    {
        $newKeys = [];
        foreach ($keys as $key) {
            $newKeys[$this->createKey($key)] = $key;
        }
        $results = $this->cacheStorage->gets(array_keys($newKeys));
        $return = [];
        foreach ($results as $key => $value) {
            $return[$newKeys[$key]] = $value;
        }
        return $return;
    }

    public function delete(string $key)
    {
        $this->cacheStorage->delete($this->createKey($key));
    }

    public function deleteMany(array $keys)
    {
        $newKeys = [];
        foreach ($keys as $key) {
            $newKeys[] = $this->createKey($key);
        }
        $this->cacheStorage->deleteMany($newKeys);
    }

    public function increment(string $key, int $by = 1)
    {
        return $this->cacheStorage->increment($this->createKey($key), $by);
    }

    public function decrement(string $key, int $by = 1)
    {
        return $this->cacheStorage->decrement($this->createKey($key), $by);
    }

    public function clear()
    {
        if ($this->threadId) {
            $this->namespaceCounter = (int) $this->cacheStorage->increment('key_counter');
        } else {
            $this->cacheStorage->clear();
        }
    }

    public function getFreeMemory(): int
    {
        return $this->cacheStorage->getFreeMemory();
    }

    public function getAvailableMemory(): int
    {
        return $this->cacheStorage->getAvailableMemory();
    }

    private function createKey(string $key): string
    {
        $prefix = 'cache_' . $this->keyPrefix;
        if ($this->threadId) {
            return $prefix . $key . '_' . $this->threadId . '_' . $this->namespaceCounter;
        }
        return $prefix . $key . '_' .  $this->mode;
    }
}
