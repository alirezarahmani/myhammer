<?php
namespace MyHammer\Library\Service;

class MemcachedService
{

    /**
     * @var \Memcached
     */
    private $memcached;

    public function get(string $key)
    {
        $value = $this->executeCommand(
            'get',
            [$key],
            [
                'type' => 'hit',
                'key' => $key,
                'details' => '1key',
                'cache_reads' => 1,
                'closure' => function ($value) {
                    $params = [];
                    if ($value === false) {
                        $params['type'] = 'miss';
                    } else {
                        $params['cache_hits'] = 1;
                        $params['value'] = strlen(print_r($value, true)) . 'B';
                    }
                    return $params;
                }
            ]
        );
        if (!$value) {
            if ($this->getMemcached()->getResultCode() == \Memcached::RES_NOTFOUND) {
                return null;
            }
            return false;
        }
        return $value;
    }

    public function set(string $key, $value)
    {
        $this->executeCommand(
            'set',
            [$key, $value, 0],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'value' => strlen(print_r($value, true)) . 'B'
            ]
        );
    }

    public function getStats(): array
    {
        $stats = [];
        $hosts = $this->serviceSettings()['memcached']['hosts'];
        foreach ($hosts as $host) {
            preg_match('/(?P<protocol>\w+):\/\/(?P<host>[0-9a-z._]*):(?P<port>\d+)/', $host, $matches);
            $memcached = new \Memcached();
            $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
            $memcached->addServer($matches['host'], $matches['port']);
            $stats = array_merge($stats, $memcached->getStats());
            $memcached = null;
        }
        return $stats;
    }

    public function inc(string $key): int
    {
        $val = $this->executeCommand(
            'increment',
            [$key],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'value' => 1
            ]
        );
        if ($this->getMemcached()->getResultCode() == \Memcached::RES_NOTFOUND) {
            $this->set($key, 1);
            return 1;
        }
        return $val;
    }

    public function incBy(string $key, int $by): int
    {
        $val = $this->executeCommand(
            'increment',
            [$key, $by],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'value' => $by
            ]
        );
        if ($this->getMemcached()->getResultCode() == \Memcached::RES_NOTFOUND) {
            $this->set($key, $by);
            return $by;
        }
        return $val;
    }

    public function incExpire(string $key, int $ttl): int
    {
        //TODO change if this bug is fixed in memcahced plugin (setting ttl in incr)
        $val = $this->executeCommand(
            'increment',
            [$key],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'expiration' => $ttl,
                'value' => 1
            ]
        );
        if ($this->getMemcached()->getResultCode() == \Memcached::RES_NOTFOUND) {
            $this->setExpire($key, 1, $ttl);
            return 1;
        }
        return $val;
    }

    public function dec(string $key): int
    {
        $val = $this->executeCommand(
            'dec',
            [$key],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'value' => 1
            ]
        );
        if ($this->getMemcached()->getResultCode() == \Memcached::RES_NOTFOUND) {
            $this->set($key, -1);
            return -1;
        }
        return $val;
    }

    public function decBy(string $key, int $by): int
    {
        $val = $this->executeCommand(
            'decrement',
            [$key, $by],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'value' => $by
            ]
        );
        if ($this->getMemcached()->getResultCode() == \Memcached::RES_NOTFOUND) {
            $this->set($key, $by * -1);
            return $by * -1;
        }
        return $val;
    }

    public function setExpire(string $key, $value, int $ttl)
    {
        if ($ttl > 2592000) {
            throw new InvalidArgumentException("TTL too big: $ttl");
        }
        $this->logUpdates++;
        $this->executeCommand(
            'set',
            [$key, $value, $ttl],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'expiration' => $ttl,
                'value' => strlen(print_r($value, true)) . 'B'
            ]
        );
    }

    public function delete(string $key)
    {
        $this->logUpdates++;
        return $this->executeCommand(
            'delete',
            [$key],
            [
                'type' => 'delete',
                'key' => $key,
                'details' => '1key',
                'cache_deletes' => 1,
            ]
        );
    }

    public function deleteMany(array $keys)
    {
        $this->logUpdates++;
        $count = count($keys);
        return $this->executeCommand(
            'deleteMulti',
            [$keys],
            [
                'type' => 'delete',
                'key' =>  $count . 'keys',
                'details' => $count . 'keys',
                'cache_deletes' => 1,
            ]
        );
    }

    public function lock(string $key, int $ttl): bool
    {
        $this->logUpdates++;
        $val = $this->executeCommand(
            'add',
            [$key, $ttl],
            [
                'type' => 'write',
                'key' => $key,
                'details' => '1key',
                'cache_writes' => 1,
                'expiration' => $ttl,
                'closure' => function ($value) {
                    return ['value' => $value];
                }
            ]
        );
        if ($val) {
            $this->setExpire($key, 1, $ttl);
        }
        return $val;
    }

    public function unlock(string $key)
    {
        $this->delete($key);
    }

    public function gets(array $keys)
    {
        $this->logSelects++;
        $values = $this->executeCommand(
            'getMulti',
            [$keys],
            [
                'type' => 'hit',
                'key' => count($keys) . ' keys',
                'cache_reads' => 1,
                'closure' => function ($value) use ($keys) {
                    $params = [];
                    $misses = count($keys) - count($value);
                    $params['details'] = $misses . 'misses';
                    if ($misses) {
                        $params['type'] = 'miss';
                    } else {
                        $params['cache_hits'] = 1;
                    }
                    $params['value'] = strlen(print_r($value, true)) . 'B';
                    return $params;
                }
            ]
        );
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $values[$key] ?? null;
        }
        return $results;
    }

    public function flush()
    {
        $hosts = $this->serviceSettings()['memcached']['hosts'];
        foreach ($hosts as $host) {
            preg_match('/(?P<protocol>\w+):\/\/(?P<host>[0-9a-z._]*):(?P<port>\d+)/', $host, $matches);
            $memcached = new \Memcached();
            $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
            $memcached->addServer($matches['host'], $matches['port']);
            $memcached->flush();
            $memcached = null;
        }
    }

    public function getSelectQueriesCount(): int
    {
        return $this->logSelects;
    }

    public function getUpdateQueriesCount(): int
    {
        return $this->logUpdates;
    }

    private function executeCommand(string $command, array $args = [], array $log = [])
    {
        $memcached = $this->getMemcached();
        $logEnabled = $log && $this->logger;
        $this->logger->startCommand($command, $log);
        $value = call_user_func_array([$memcached, $command], $args);
        if ($logEnabled) {
            $this->logger->stopCommand(isset($log['closure']) ? $log['closure']($value) : []);
        }
        $resultCode = $memcached->getResultCode();
        if ($resultCode != \Memcached::RES_SUCCESS) {
            if ($resultCode != \Memcached::RES_NOTFOUND && $resultCode != \Memcached::RES_NOTSTORED) {
                throw new \Exception("Invalid response from memcached: " . $memcached->getResultMessage());
            }
        }
        return $value;
    }

    private function getMemcached(): \Memcached
    {
        if ($this->memcached === null) {
            $settings = $this->serviceSettings()['memcached'];
            preg_match('/(?P<protocol>\w+):\/\/(?P<host>[0-9a-z._]*):(?P<port>\d+)/', $settings['mcrouter'], $matches);
            $this->memcached = new \Memcached();
            $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
            $this->memcached->addServer($matches['host'], $matches['port']);
        }
        return $this->memcached;
    }
}
