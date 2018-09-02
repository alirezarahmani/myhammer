<?php
namespace MyHammer\Infrastructure\Validator;

use MyHammer\Domain\Model\Entity\DemandEntity;
use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Library\Assert\Assert;

class ApiDemandValidator implements ApiValidatorInterface
{
    public function validate(ApiRequestInterface $request)
    {
        $assert = Assert::lazy()->initArray(
            [
            'title' => $request->get('title'),
            'zip_code' => $request->get('zip_code'),
            'city' => $request->get('city'),
            'description' => $request->get('description'),
            'execution_time' => $request->get('execution_time'),
            'category_id' => $request->get('category_id'),
        ]);
        $assert
            ->thatInArray('title')
            ->notEmpty('Title: allows between 5 to 50 characters')
            ->maxLength(55, 'Title: allows between 5 to 50 characters')
            ->minLength(5, 'Title: allows between 5 to 50 characters')
            ->thatInArray('zip_code')
            ->notEmpty('Zip: should not be empty')
            ->inArray(Address::getZipCodes(), 'Zip: german zipcode only')
            ->thatInArray('city')
            ->notEmpty('City: should not be empty')
            ->inArray(Address::getCities(), 'City: german city only')
            ->thatInArray('execution_time')
            ->inArray(DemandEntity::EXECUTION_TIMES, 'Execution Time: sorry please make sure you select correct execution time format')
            ->thatInArray('category_id')
            ->satisfy(CustomValidations::isValidCategoryId(), 'Category ID: not a valid category id')
            ->thatInArray('description')
            ->notEmpty('Description: should not be empty');

        $assert->verifyNow();
    }
}
