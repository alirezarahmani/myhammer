<?php

namespace Loader;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    public static function init()
    {
        try {
            $route = new Route('/foo', array('_controller' => 'MyController','_method' => 'create'),[],[],'',[],'POST');
            $routes = new RouteCollection();
            $routes->add('ok', $route);
            $request = Container::load()->get(Request::class);

            $context = new RequestContext('/');
            $context->fromRequest($request);

            $matcher = new UrlMatcher($routes, $context);

            $parameters = $matcher->match($request->getRequestUri());
            var_dump($parameters);exit;
            return $parameters['_controller'];
        } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            echo 'not';
            return new JsonResponse('not found' , 404);
        }
    }
}
