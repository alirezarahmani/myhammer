<?php

namespace MyHammer\Infrastructure\Repositories;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\RepositoryInterface;

class DemandRepository implements RepositoryInterface
{

    public function update(DemandEntity $demandEntity, array $inputs)
    {
        $demandEntity->setTitle($inputs['title']);
        $demandEntity->setCategoryId($inputs['category_id']);
        $demandEntity->setAddress($inputs['address']);
        $demandEntity->setExecutionTime($inputs['execution_time']);
        $demandEntity->setDescription($inputs['description']);
        // @todo: fix UserID, Remove HardCode
        $demandEntity->setUserId(1);
        $demandEntity->flush();
    }
}