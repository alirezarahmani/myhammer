<?php
namespace MyHammer\Library\Entity;

class SortableEntityCacheIndex extends EntityCacheIndex
{

    private $columns;

    public function __construct(array $columns, array $fields, int $maxPages = Entity::ROWS_IN_CACHED_PAGE)
    {
        parent::__construct(false, $fields, $maxPages);
        $this->columns = $columns;
    }

    public function getSortColumns(): array
    {
        return $this->columns;
    }
}
