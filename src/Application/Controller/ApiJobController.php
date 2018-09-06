<?php

namespace MyHammer\Application\Controller;

use MyHammer\Infrastructure\Repositories\DemandRepository;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Request\ApiResponseInterface;
use MyHammer\Library\Assert\ValidateException;
use MyHammer\View\ApiJobSearchView;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApiJobController
{
    public function indexAction(ApiRequestInterface $request, ApiResponseInterface $response)
    {
        try {
            //@todo: add some validation to search params
            return $response->success((new ApiJobSearchView())->search(
                $request,
                new DemandRepository()
            ));
        } catch (ValidateException $e) {
            return $response->error($e->getErrors());
        }
    }
}
