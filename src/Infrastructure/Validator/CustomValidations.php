<?php
namespace MyHammer\Infrastructure\Validator;

use MyHammer\Domain\Model\Entity\CategoryEntity;
use MyHammer\Library\Entity\Exception\EntityNotFoundException;

class CustomValidations
{
    public static function isValidCategoryId()
    {
        return function ($brandId) {
            try {
                CategoryEntity::getById($brandId);
                return true;
            } catch (EntityNotFoundException $exception) {
                return false;
            }
        };
    }

}