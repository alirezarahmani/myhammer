<?php
namespace MyHammer\View;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\RepositoryInterface;
use MyHammer\Infrastructure\Request\ApiRequestInterface;

class ApiJobSearchView
{
    public function search(
        ApiRequestInterface $apiRequest,
        RepositoryInterface $repository
    ) {
        $filters = $this->getFilters($apiRequest->getRequest());
        $data = $repository->searchJob($filters);
        $result = [];
        /** @var DemandEntity $datum */
        foreach ($data as $datum) {
            $result[] = [
               'title' => $datum->getTitle(),
               'category' => $datum->getCategory()->getId(),
               'execute time' => $datum->getExecutionTime(),
               'address' => (string)$datum->getAddress()
            ];
        }
        return $result;
    }

    private function getFilters($request)
    {
        return array_filter($request->query->getIterator()->getArrayCopy(), function ($key) {
            return in_array($key, ['city', 'zip_code', 'category_id']);
        }, ARRAY_FILTER_USE_KEY);
    }
}
