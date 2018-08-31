<?php
namespace Digikala\Supernova\Lib\Assert;

use Assert\InvalidArgumentException;
use Assert\LazyAssertionException;

class ValidateException extends LazyAssertionException
{

    private $errors = [];
    private $values = [];

    public function __construct(string $message = null, string $code = null, $value = null)
    {
        parent::__construct('', []);
        if ($message) {
            $this->addError($message, $code, $value);
        }
    }

    public static function fromErrors(array $errors)
    {
        $exception = new ValidateException()
        /**
         * @var InvalidArgumentException[] $errors
         */;
        foreach ($errors as $error) {
            $exception->addError($error->getMessage(), $error->getPropertyPath(), $error->getValue());
        }
        return $exception;
    }

    public function addError(string $message, string $code = null, $value = null): self
    {
        if ($code === null) {
            $this->errors[] = $message;
            $this->values[] = $value;
        } else {
            $this->errors[$code] = $message;
            $this->values[$code] = $value;
        }
        $this->message = print_r($this->errors, true) . print_r($this->values, true);
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorExceptions()
    {
        $errors = [];
        foreach ($this->errors as $key => $message) {
            $errors[$key] = new InvalidArgumentException($message, 0, $key, $this->values[$key]);
        }
        return $errors;
    }

    public function hasError(string $code): bool
    {
        return isset($this->errors[$code]);
    }

    public function getError(string $code): ?string
    {
        return $this->errors[$code] ?? null;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getValue()
    {
        return $this->values ? array_values($this->values)[0] : null;
    }
}
