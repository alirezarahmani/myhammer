<?php

namespace MyHammer\Application\Controller;

use MyHammer\Domain\Model\ApiModifyDemandDomainModel;
use MyHammer\Domain\Model\ApiRegisterDemandDomainModel;
use MyHammer\Infrastructure\Repositories\DemandRepository;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Request\ApiResponseInterface;
use MyHammer\Infrastructure\Validator\ApiDemandValidator;
use MyHammer\Library\Assert\ValidateException;
use MyHammer\Library\Entity\Exception\EntityNotFoundException;

class ApiDemandController
{
    public function createAction(ApiRequestInterface $request, ApiResponseInterface $response)
    {
        try {
            (new ApiRegisterDemandDomainModel())->register($request, new ApiDemandValidator(), new DemandRepository());
            return $response->success();
        } catch (ValidateException $e) {
            return $response->error($e->getErrors());
        }
    }

    public function editAction(int $id, ApiRequestInterface $request, ApiResponseInterface $response)
    {
        try {
            (new ApiModifyDemandDomainModel())->edit($id, $request, new ApiDemandValidator(), new DemandRepository());
            return $response->success();
        } catch (ValidateException $e) {
            return $response->error($e->getErrors());
        } catch (EntityNotFoundException $e) {
            return $response->error(['Sorry, no demand found with id: ' . $id]);
        }
    }
}
