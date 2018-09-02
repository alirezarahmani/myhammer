<?php

namespace MyHammer\Application\Controller;

use MyHammer\Domain\Model\ApiDemandDomainModel;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Request\ApiResponseInterface;
use MyHammer\Infrastructure\Validator\ApiDemandValidator;
use MyHammer\Library\Assert\ValidateException;

class DemandController
{
    public function createAction(ApiRequestInterface $request, ApiResponseInterface $response)
    {
        try {
            (new ApiDemandDomainModel())->add($request, new ApiDemandValidator());
            return $response;
        } catch (ValidateException $e) {
            return $response->error($e->getMessage());
        }
    }

    public function editAction(int $id, ApiRequestInterface $request, ApiResponseInterface $response)
    {
        var_dump($id);exit;
        try {
            (new ApiDemandDomainModel())->edit($id, $request, new ApiDemandValidator());
            return $response;
        } catch (ValidateException $e) {
            return $response->error($e->getMessage());
        }
    }
}
