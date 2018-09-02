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
        $demand->setExecutionTime($request->get('execution_time'));
        $demand->setDescription($request->get('description'));
        // @todo: fix UserID, Remove Hard Code
        $demand->setUserId(1);
        $demand->flush();
    }

    public function edit(int $id, ApiRequestInterface $request, ApiValidatorInterface $validator)
    {
        $validator->validate($request);
        $demand = DemandEntity::getById($id);
        $demand->setTitle($request->get('title'));
        $demand->setCategoryId($request->get('category_id'));
        $demand->setAddress($request->get('address'));
        $demand->setExecutionTime($request->get('execution_time'));
        $demand->setDescription($request->get('description'));
        $demand->setUserId(1);
        $demand->flush();
    }
}
