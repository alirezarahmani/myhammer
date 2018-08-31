<?php
namespace MyHammer\Library\Assert;

class AssertionChain extends \Assert\AssertionChain
{
    private $array = [];

    public function getArray() : array
    {
        return $this->array;
    }

    public function initArray(array $array) : self
    {
        $this->array = $array;

        return $this;
    }

    public function get(string $key, $default = null)
    {
        if (array_key_exists($key, $this->array)) {
            return $this->array[$key];
        }

        $this->array[$key] = $default;

        return $this->array[$key];
    }

    public function setDefault(string $key, $default): self
    {
        $this->get($key, $default);

        return $this;
    }

    public function set(string $key, $value)
    {
        $this->array[$key] = $value;
    }

    public function cast2bool(string $key) : bool
    {
        $this->array[$key] = array_key_exists($key, $this->array);

        return $this->array[$key];
    }

    public function remove(string $key)
    {
        unset($this->array[$key]);
    }

    public function thatInArray(string $key, string $propertyPath = null, $defaultMessage = null): self
    {
        return $this->that($this->get($key), $propertyPath ?? $key, $defaultMessage);
    }
}
