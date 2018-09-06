<?php
namespace MyHammer\Domain\Model;

use Loader\MyHammer;
use MyHammer\Domain\Events\ApiRegisterDemandEvent;
use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\RepositoryInterface;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Validator\ApiValidatorInterface;
use MyHammer\Infrastructure\Validator\CustomValidations;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
            $apiRequest->getRequest()->query->getIterator()->getArrayCopy(),
            new CustomValidations()
        );
        //@todo: complete event, add data to ApiRegisterDemandEvent
        MyHammer::getContainer()->get(EventDispatcher::class)->dispatch(
            ApiRegisterDemandEvent::EVENT_NAME,
            new ApiRegisterDemandEvent([])
        );
        //@todo: complete this
    }
}
