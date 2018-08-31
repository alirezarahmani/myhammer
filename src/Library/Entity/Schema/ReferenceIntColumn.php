<?php
namespace MyHammer\Library\Entity\Schema;

class ReferenceIntColumn extends BaseReferenceIntColumn
{

    public static function create(string $name, string $parentEntityClass): self
    {
        return new ReferenceIntColumn($name, $parentEntityClass);
    }
}
