#!/bin/bash
set -e
eval $(stat -c 'usermod -u %u -g %g www-data' /var/www) || true
/etc/init.d/php7.2-fpm start
exec "$@"
