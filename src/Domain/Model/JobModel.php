<?php
namespace MyHammer\Domain\Model;

use MyHammer\Domain\Model\Entity\CategoryEntity;
use MyHammer\Domain\Model\Entity\JobEntity;
use MyHammer\Infrastructure\Validator\Validator;
use MyHammer\Library\Assert\Assertion;

class JobModel
{
    public function add(Validator $validator)
    {
        $data = $validator->getData();
        $category = CategoryEntity::getById($data['category_id']);

        $job = JobEntity::newInstance();
        $job->setTitle($data['title']);
        $job->setCategoryId($category->getId());
        $job->setAddress($data['address']);
        $job->setDescription($data['description']);
        $job->flush();
    }
}
