<?php

use MyHammer\Infrastructure\Request\ApiJsonResponse;
use MyHammer\Infrastructure\Request\ApiWebRequest;

opcache_reset();
require getenv('VENDOR_DIR') . '/autoload.php';
$container = \Loader\Container::load();
//\Loader\Router::init();

return (new \MyHammer\Application\Controller\DemandController())->createAction( new \MyHammer\Infrastructure\Request\ApiApplicationRequest(new \Symfony\Component\HttpFoundation\Request()), new ApiJsonResponse());
