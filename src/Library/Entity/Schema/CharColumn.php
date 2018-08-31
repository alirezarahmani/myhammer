<?php
namespace MyHammer\Library\Entity\Schema;

class CharColumn extends LengthTextColumn
{

    public function __construct(string $name, int $length)
    {
        parent::__construct($name, $length);
    }

    public static function create(string $name, int $length): self
    {
        return new CharColumn($name, $length);
    }

    protected function getColumnDefinition(): string
    {
        return 'char';
    }
}
