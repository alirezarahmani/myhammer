<?php
namespace MyHammer\Library\Entity;

interface DirtyInterface
{
    public function isDirty(): bool;
}
