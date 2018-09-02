<?php

namespace MyHammer\Application\Controller;

use MyHammer\Domain\Model\ApiDemandDomainModel;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Request\ApiResponseInterface;
use MyHammer\Infrastructure\Validator\ApiDemandValidator;
use MyHammer\Library\Assert\ValidateException;
use MyHammer\Library\Entity\Exception\EntityNotFoundException;

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
}
