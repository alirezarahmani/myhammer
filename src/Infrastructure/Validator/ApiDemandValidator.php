<?php
namespace MyHammer\Infrastructure\Validator;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Library\Assert\Assert;

class ApiDemandValidator implements ApiValidatorInterface
{
    public function validate(ApiRequestInterface $request, CustomValidationsInterface $customValidation = null)
    {
        $assert = Assert::lazy()->initArray(
            $request->getRequest()->query->getIterator()->getArrayCopy()
        );

        $assert
            ->thatInArray('title')
            ->notEmpty('Title: must not be empty')
            ->maxLength(55, 'Title: allows between 5 to 50 characters')
            ->minLength(5, 'Title: allows between 5 to 50 characters')
            ->thatInArray('zip_code')
            ->notEmpty('Zip: must not be empty')
            ->inArray(Address::getZipCodes(), 'Zip: german zip code only')
            ->thatInArray('city')
            ->notEmpty('City: should not be empty')
            ->inArray(Address::getCities(), 'City: german city only')
            ->thatInArray('execute_time')
            ->inArray(DemandEntity::EXECUTE_TIMES, 'Execution Time: sorry please make sure you select correct execution time from list')
            ->thatInArray('category_id')
            ->notEmpty('category Id: should not be empty')
            ->satisfy($customValidation->isValidCategoryId(), 'Category ID: is not valid')
            ->thatInArray('description')
            ->notEmpty('Description: should not be empty');
        $assert->verifyNow();
    }
}
