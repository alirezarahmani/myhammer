#!/bin/bash
set -e
/usr/bin/memcached -u memcache -p 11211 -d
/usr/bin/memcached -u memcache -p 11212 -d
/usr/bin/memcached -u memcache -p 11213 -d

exec "$@"