<?php

namespace MyHammer\Infrastructure\Repositories;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Domain\RepositoryInterface;
use MyHammer\Library\Service\Mysql\Expression;
use MyHammer\Library\Service\Mysql\Pager;

class DemandRepository implements RepositoryInterface
{

    public function update(DemandEntity $demandEntity, array $inputs)
    {
        $demandEntity->setTitle($inputs['title']);
        $demandEntity->setCategoryId($inputs['category_id']);
        $demandEntity->setAddress(new Address($inputs['city'], $inputs['zip_code']));
        $demandEntity->setExecuteTime($inputs['execute_time']);
        $demandEntity->setDescription($inputs['description']);
        // @todo: fix UserID, Remove HardCode
        $demandEntity->setUserId(1);
        // @todo: add it to event of entity
        if (empty($demandEntity->getCreatedAt())) {
            $demandEntity->setCreatedAt(new \DateTime());
        }
        $demandEntity->setUpdatedAt(new \DateTime());
        $demandEntity->flush();
    }

    public function searchJob($filters = null):?array
    {
        $query = 'created_at >= ? ';
        $params[] = date('Y-m-d h:m:s', strtotime('-30 days'));
        foreach ($filters as $index => $filter) {
            $query .= ' AND ' . $index . ' = ? ';
            $params[] = $filter;
        }
        //@todo: add pagination
        return DemandEntity::getManyByQuery(
            new Expression($query, $params),
            new Pager(1, 10)
        );
    }
}
