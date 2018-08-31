<?php
namespace MyHammer\Library\Entity;

class EntityCacheIndex
{

    private $unique;
    private $fields;
    private $maxCachePages;

    public function __construct(bool $unique, array $fields, int $maxRows = Entity::ROWS_IN_CACHED_PAGE)
    {
        $this->unique = $unique;
        $this->fields = $fields;
        $this->maxCachePages = ceil($maxRows / Entity::ROWS_IN_CACHED_PAGE);
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getMaxCachePages(): int
    {
        return $this->maxCachePages;
    }
}
