<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Validator\ApiValidatorInterface;


class ApiDemandDomainModel
{
    public function add(ApiRequestInterface $request, ApiValidatorInterface $validator)
    {
        $validator->validate($request);
        $demand = DemandEntity::newInstance();
        $demand->setTitle($request->get('title'));
        $demand->setCategoryId($request->get('category_id'));
        $demand->setAddress($request->get('address'));
        $demand->setDescription($request->get('description'));
        $demand->flush();
    }
}
