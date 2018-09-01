<?php

namespace MyHammer\Application\Controller;

use MyHammer\Domain\Model\ApiDemandModel;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Request\ApiResponseInterface;
use MyHammer\Infrastructure\Validator\ApiDemandValidator;
use MyHammer\Library\Assert\ValidateException;

class DemandController
{
    public function createAction(ApiRequestInterface $request, ApiResponseInterface $response)
    {
        try {
            (new ApiDemandModel())->add($request, new ApiDemandValidator());
            return $response;
        } catch (ValidateException $e) {
            return $response->error($e->getMessage());
        }
    }
}
