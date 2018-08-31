<?php
namespace Digikala\Supernova\Lib\Assert;

class Assertion extends \Assert\Assertion
{
    protected static function createException($value, $message, $code, $propertyPath = null, array $constraints = array())
    {
        return new ValidateException($message, $propertyPath, $value);
    }
}
