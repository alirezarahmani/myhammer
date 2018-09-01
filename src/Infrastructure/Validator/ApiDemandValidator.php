<?php
namespace MyHammer\Infrastructure\Validator;

use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Infrastructure\Request\ApiRequestInterface;
use MyHammer\Library\Assert\Assert;

class ApiDemandValidator implements ApiValidatorInterface
{
    public function validate(ApiRequestInterface $request)
    {
        $title = $request->get('title');
        $zipCode =  $request->get('zip_code');

        $assert = Assert::lazy()->initArray([
            'title' => $title,
            'zipcode' => $zipCode,
            'city' => $request->get('city'),
            'description' => $request->get('description'),
        ]);
        $assert
            ->thatInArray('title')
            ->notEmpty('title: allows between 5 to 50 characters')
            ->maxLength(55, 'title: allows between 5 to 50 characters')
            ->minLength(5, 'title: allows between 5 to 50 characters')
            ->thatInArray('zip_code')
            ->notEmpty('Zip: should not be empty')
            ->inArray(Address::getZipCodes(), 'zip: german zipcode only')
            ->thatInArray('city')
            ->notEmpty('city: should not be empty')
            ->inArray(Address::getCities(), 'german city only')
            ->thatInArray('description')
            ->notEmpty('description should not be empty');
        $assert->verifyNow();
    }
}
