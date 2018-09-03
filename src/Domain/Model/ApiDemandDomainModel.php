<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\RepositoryInterface;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Validator\ApiValidatorInterface;

class ApiDemandDomainModel
{
    public function add(
        ApiRequestInterface $apiRequest,
        ApiValidatorInterface $validator,
        RepositoryInterface $repository
    ) {
        $validator->validate($apiRequest);
        $repository->create(
            DemandEntity::newInstance(),
            $apiRequest->request->query->getIterator()->getArrayCopy()
        );
    }

    public function edit(
        int $id,
        ApiRequestInterface $apiRequest,
        ApiValidatorInterface $validator,
        RepositoryInterface $repository
    ) {
        $validator->validate($apiRequest);
        $repository->create(
            DemandEntity::getById($id),
            $apiRequest->request->query->getIterator()->getArrayCopy()
        );
    }
}
