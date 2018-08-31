<?php
namespace MyHammer\Library\Entity\Schema;

class VarcharColumn extends LengthTextColumn
{

    public static function create(string $name, int $length = 255): self
    {
        return new VarcharColumn($name, $length);
    }

    protected function getColumnDefinition(): string
    {
        return 'varchar';
    }
}
