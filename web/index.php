<?php
opcache_reset();
require getenv('VENDOR_DIR') . '/autoload.php';
$container = \Loader\Container::load();
\Loader\Router::init();