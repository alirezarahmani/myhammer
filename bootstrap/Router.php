<?php

namespace Loader;

use MyHammer\Application\Controller\DemandController;
use MyHammer\Infrastructure\Request\ApiApplicationRequest;
use MyHammer\Infrastructure\Request\ApiJsonResponse;
use MyHammer\Infrastructure\Request\ApiWebRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Router
{
    public static function init()
    {
        $demandsController = new DemandController();
        /** @var Request $request */
        $request = Container::load()->get(Request::class);
        $apiRequest = new ApiWebRequest($request);
        $apiResponse = new ApiJsonResponse();
        $route = $request->getRequestUri();
        if (self::isMobileRequest($request)) {
            $apiRequest = new ApiApplicationRequest($request);
        }
        switch ($request->getMethod()) {
            case 'POST':
                if ($route == '/demands') {
                    $demandsController->createAction($apiRequest, $apiResponse);
                }
                break;
            case 'GET':
                // @TODO: complete
                break;
            case 'PUT':
                $route = explode('/', $route);
                if (isset($route[2])) {
                    if (preg_match('/^[1-9][0-9]*$/', $route[2])
                        && $route[1] == 'demands' && empty($route[0])
                    ) {
                        $demandsController->editAction($route[2], $apiRequest, $apiResponse);
                    }
                }
                break;
            case 'DELETE':
                // @TODO: complete
                break;
        }
        return new JsonResponse('Not Found', 404);
    }

    public static function isMobileRequest(Request $request)
    {
        if ($request->headers->has('device-type') &&
            strpos($request->headers->get('device-type'), 'mobile') !== false
        ) {
            return true;
        }
        return false;
    }
}
