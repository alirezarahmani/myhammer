<?php
namespace MyHammer\Library\Entity;

interface EntityCacheFieldsProvider
{
    public function getCachedFields(Entity $entity): array;
}
