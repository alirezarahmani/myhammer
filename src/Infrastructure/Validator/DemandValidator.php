<?php
namespace MyHammer\Infrastructure\Validator;

use MyHammer\Domain\Model\ValueObject\Address;
use MyHammer\Infrastructure\Request\RequestInterface;
use MyHammer\Library\Assert\Assert;

class DemandValidator implements ValidatorInterface
{
    private $input;

    public function __construct(RequestInterface $request)
    {
        $this->input = $request;
    }

    public function validate()
    {
        $title = $this->input->get('title');
        $zipCode =  $this->input->get('zip_code');

        $assert = Assert::lazy()->initArray([
            'title' => $title,
            'zipcode' => $zipCode,
            'city' => $this->input->get('city'),
            'description' => $this->input->get('description'),
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
