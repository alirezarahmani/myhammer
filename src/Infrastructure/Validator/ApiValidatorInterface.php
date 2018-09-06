<?php

namespace MyHammer\Infrastructure\Validator;

use MyHammer\Infrastructure\Request\ApiRequestInterface;

interface ApiValidatorInterface
{
    public function validate(ApiRequestInterface $request, CustomValidationsInterface $customValidation = null);
}
