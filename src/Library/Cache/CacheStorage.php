<?php
namespace MyHammer\Library\Cache;

interface CacheStorage
{

    public function set(string $key, $value, int $ttl);

    public function get(string $key);

    public function gets(array $keys): array;

    public function delete(string $key);

    public function deleteMany(array $keys);

    public function increment(string $key, int $by = 1): int;

    public function decrement(string $key, int $by = 1): int;

    public function clear();

    public function iterateKeys(string $prefix = null) : \Iterator;

    public function getFreeMemory() : int;

    public function getAvailableMemory() : int;
}
