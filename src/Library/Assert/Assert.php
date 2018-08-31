<?php
namespace Digikala\Supernova\Lib\Assert;

use Digikala\Supernova\Lib\Entity\Entity;
use Digikala\Supernova\Lib\Entity\Exception\EntityNotFoundException;

class Assert extends \Assert\Assert
{
    public static function lazy()
    {
        $lazyAssertion = new LazyAssertion();
        $lazyAssertion->setExceptionClass(ValidateException::class);

        return $lazyAssertion;
    }

    public static function that($value, $defaultMessage = null, $defaultPropertyPath = null)
    {
        $assertionChain = new AssertionChain($value, $defaultMessage, $defaultPropertyPath);
        $assertionChain->setAssertionClassName(Assertion::class);

        return $assertionChain;
    }

    public static function thatAll($values, $defaultMessage = null, $defaultPropertyPath = null)
    {
        $parent = parent::thatAll($values, $defaultMessage, $defaultPropertyPath);
        $parent->setAssertionClassName(Assertion::class);
        return $parent;
    }

    public static function thatNullOr($value, $defaultMessage = null, $defaultPropertyPath = null)
    {
        $parent = parent::thatNullOr($value, $defaultMessage, $defaultPropertyPath);
        $parent->setAssertionClassName(Assertion::class);
        return $parent;
    }

    public static function isValidEntityId(string $entityClass)
    {
        return function ($value) use ($entityClass) {
            try {
                /**
                 * @var Entity $entityClass
                 */
                $entityClass::getById($value);
                return true;
            } catch (EntityNotFoundException $e) {
                return false;
            }
        };
    }
}
