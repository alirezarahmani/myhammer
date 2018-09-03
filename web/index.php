<?php

use Symfony\Component\HttpFoundation\Request;

opcache_reset();
require getenv('VENDOR_DIR') . '/autoload.php';
$request = Request::createFromGlobals();
\Loader\MyHammer::initialize();
\Loader\Router::init();
