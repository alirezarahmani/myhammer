<?php

namespace Loader;

use MyHammer\Infrastructure\Request\ApiApplicationRequest;
use MyHammer\Infrastructure\Request\ApiJsonResponse;
use MyHammer\Infrastructure\Request\ApiWebRequest;
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
    //@todo: create a router event and remove this
    public static function init()
    {
        try {
            $request = Container::load()->get(Request::class);
            $apiRequest = new ApiWebRequest($request);
            $apiResponse = new ApiJsonResponse();

            if ($request->headers->has('device-type') && $request->headers->get('device-type') == 'mobile') {
                $apiRequest = new ApiApplicationRequest($request);
            }

            $context = new RequestContext('/');
            $context->fromRequest($request);
            $matcher = new UrlMatcher(self::initRoutes(), $context);
            $parameters = $matcher->match($request->getRequestUri());

            if(in_array('id', $parameters)) {
                 return (new $parameters['_controller'])->{$parameters['_method']}($parameters['id'],$apiRequest, $apiResponse);
            }

            return (new $parameters['_controller'])->{$parameters['_method']}($apiRequest, $apiResponse);
        } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            return new JsonResponse('not found',404);
        }
    }

    private static function initRoutes()
    {
        $addRoute = new Route(
            '/demands',
            ['_controller' => 'MyHammer\\Application\\Controller\\DemandController', '_method' => 'createAction'], [], [],'',[],'GET');
        $editRoute = new Route('/demands/{id}', ['_controller' => 'MyHammer\\Application\\Controller\\DemandController', '_method' => 'editAction'], [], [],'', [],'GET');
        $routes = new RouteCollection();
        $routes->add('demands_add', $addRoute);
        $routes->add('demands_edit', $editRoute);
        return $routes;
    }
}
