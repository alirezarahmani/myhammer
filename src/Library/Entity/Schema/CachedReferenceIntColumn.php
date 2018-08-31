<?php
namespace MyHammer\Library\Entity\Schema;

class CachedReferenceIntColumn extends BaseReferenceIntColumn
{

    private $cachePageSize;
    private $orderPart;

    protected function __construct(string $name, string $parentEntityClass, int $cachePageSize, string $orderPart)
    {
        parent::__construct($name, $parentEntityClass);
        $this->cachePageSize = $cachePageSize;
        $this->orderPart = $orderPart;
    }

    public static function create(string $name, string $parentEntityClass, int $cachePageSize = 100, string $orderPart = 'id ASC'): self
    {
        return new CachedReferenceIntColumn($name, $parentEntityClass, $cachePageSize, $orderPart);
    }

    public function getCachePageSize(): int
    {
        return $this->cachePageSize;
    }

    public function getOrderPart(): string
    {
        return $this->orderPart;
    }
}
