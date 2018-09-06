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
    //@todo: create a routerSubscriber and remove this
    //@todo: create a dependency resolver for controller arguments
    public static function routes()
    {
        try {
            /** @var Request $request */
            $request = MyHammer::getContainer()->get(Request::class);
            $apiRequest = new ApiWebRequest($request);
            $apiResponse = new ApiJsonResponse();

            if ($request->headers->has('device-type') &&
                $request->headers->get('device-type') == 'mobile'
            ) {
                $apiRequest = new ApiApplicationRequest($request);
            }

            $context = new RequestContext('/');
            $context->fromRequest($request);
            $matcher = new UrlMatcher(self::initRoutes(), $context);
            if ($parameters = $matcher->match($request->getPathInfo())) {
                if (isset($parameters['id'])) {
                    return call_user_func(
                        [$parameters['_controller'], $parameters['_method']],
                        $parameters['id'],
                        $apiRequest,
                        $apiResponse
                    );
                }
                return call_user_func(
                    [$parameters['_controller'], $parameters['_method']],
                    $apiRequest,
                    $apiResponse
                );
            }
        } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            return (new JsonResponse(['sorry requested page not found'], 404))->send();
        }
    }

    private static function initRoutes()
    {
        $addRoute = new Route(
            '/demands',
            ['_controller' => 'MyHammer\\Application\\Controller\\ApiDemandController', '_method' => 'createAction'],
            [],
            [],
            '',
            [],
            'GET'
        );
        $editRoute = new Route(
            '/demands/{id}',
            ['_controller' => 'MyHammer\\Application\\Controller\\ApiDemandController', '_method' => 'editAction'],
            [],
            [],
            '',
            [],
            'GET'
        );
        $indexRoute = new Route(
            '/jobs',
            ['_controller' => 'MyHammer\\Application\\Controller\\ApiJobController', '_method' => 'indexAction'],
            [],
            [],
            '',
            [],
            'GET'
        );
        $routes = new RouteCollection();
        $routes->add('demands_add', $addRoute);
        $routes->add('demands_edit', $editRoute);
        $routes->add('job_index', $indexRoute);
        return $routes;
    }
}
