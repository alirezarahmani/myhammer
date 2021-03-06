<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\RepositoryInterface;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Validator\ApiValidatorInterface;
use MyHammer\Infrastructure\Validator\CustomValidations;

class ApiModifyDemandDomainModel
{
    public function edit(
        int $id,
        ApiRequestInterface $apiRequest,
        ApiValidatorInterface $validator,
        RepositoryInterface $repository
    ) {
        $validator->validate($apiRequest, new CustomValidations());
        $repository->update(
            DemandEntity::getById($id),
            $apiRequest->getRequest()->query->getIterator()->getArrayCopy()
        );
    }
}
