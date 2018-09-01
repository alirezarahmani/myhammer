<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\CategoryEntity;
use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Infrastructure\Validator\ApiValidatorInterface;

class ApiDemandModel
{
    public function add(ApiRequestInterface $request, ApiValidatorInterface $validator)
    {
        $validator->validate($request);
        $category = CategoryEntity::getById($request->get('category_id'));

        $demand = DemandEntity::newInstance();
        $demand->setTitle($request->get('title'));
        $demand->setCategoryId($category->getId());
        $demand->setAddress($request->get('address'));
        $demand->setDescription($request->get('description'));
        $demand->flush();
    }
}
