<?php
opcache_reset();
require getenv('VENDOR_DIR') . '/autoload.php';
\Loader\Container::load();
\Loader\Router::init();
