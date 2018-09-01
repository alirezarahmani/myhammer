<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\CategoryEntity;
use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Infrastructure\Validator\ValidatorInterface;
use MyHammer\Library\Assert\Assertion;

class WebDemandModel
{
    public function add(ValidatorInterface $validator)
    {
        $data = $validator->validate();
        $category = CategoryEntity::getById($data['category_id']);

        $job = DemandEntity::newInstance();
        $job->setTitle($data['title']);
        $job->setCategoryId($category->getId());
        $job->setAddress($data['address']);
        $job->setDescription($data['description']);
        $job->flush();
    }
}
