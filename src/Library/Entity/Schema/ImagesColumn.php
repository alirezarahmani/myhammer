<?php
namespace MyHammer\Library\Entity\Schema;

class ImagesColumn extends ColumnSchema
{
    public static function create(string $name): self
    {
        return new ImagesColumn($name);
    }

    public function getColumnDefinitionSql(): string
    {
        return "`{$this->getName()}` mediumtext";
    }
}
