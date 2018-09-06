<?php
// in dev environment only
opcache_reset();

require getenv('VENDOR_DIR') . '/autoload.php';
\Loader\MyHammer::create();
\Loader\Router::routes();
