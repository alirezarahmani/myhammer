#!/usr/bin/env bash

if [ ! -f .env ]; then
    touch .env
    echo LOCAL_IP=0.0.0.0 >> .env
    echo MYSQL_PORT=3307 >> .env
    echo PHP_SSH_PORT=23 >> .env
    echo MY_HAMMER_SERVER_PORT=81 >> .env
    echo VENDOR_DIR=/var/www/vendor >> .env
    echo LOCAL_DEV_DIR=$(pwd) >> .env
fi

docker-compose build
docker-compose up -d
docker-compose exec worker composer install
docker-compose down
docker-compose up -d