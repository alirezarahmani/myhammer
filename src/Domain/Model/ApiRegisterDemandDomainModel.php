<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\RepositoryInterface;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Validator\ApiValidatorInterface;

class ApiRegisterDemandDomainModel
{
    public function register(
        ApiRequestInterface $apiRequest,
        ApiValidatorInterface $validator,
        RepositoryInterface $repository
    ) {
        $validator->validate($apiRequest);
        $repository->update(
            DemandEntity::newInstance(),
            $apiRequest->getRequest()->query->getIterator()->getArrayCopy()
        );
    }
//
//    public function edit(
//        int $id,
//        ApiRequestInterface $apiRequest,
//        ApiValidatorInterfacTwitterApiEvent.phpe $validator,
//        RepositoryInterface $repository
//    ) {
//        $validator->validate($apiRequest);
//        $repository->create(
//            DemandEntity::getById($id),
//            $apiRequest->getRequest()->query->getIterator()->getArrayCopy()
//        );
//    }
}
