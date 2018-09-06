<?php
namespace MyHammer\Domain\Model\ValueObject;

/**
/* Note: Address like City, Zip Code and Country names Can be a value object,
/* I assume city and zip code are fixed and won't change, in this system.
/* Reference: https://stackoverflow.com/questions/1368977/ddd-should-country-be-a-value-object-or-an-entity
**/
use MyHammer\Library\Assert\Assertion;

class Address implements ValueObjectInterface
{
    private $city;
    private $zipCode;

    private const AREA = [
        'Berlin' => '10115',
        'Porta Westfalica' => '32457',
        'Lommatzsch' => '01623',
        'Hamburg' => '21521',
        'Bülzig' => '06895',
        'Diesbar-Seußlitz' => '01612'
    ];

    public function __construct(string $city, string $zipCode)
    {
        $this->city = $city;
        $this->zipCode = $zipCode;
        Assertion::inArray($zipCode, self::AREA, 'german zip code only');
        Assertion::keyExists(self::AREA, $city, 'german city only');
        Assertion::eq(self::AREA[$city], $this->zipCode, 'sorry, wrong zip code for ' . $city);
    }

    public function getZipCode():string
    {
        return $this->zipCode;
    }

    public function getCity():string
    {
        return $this->city;
    }

    public static function getZipCodes():array
    {
        return array_values(self::AREA);
    }

    public static function getCities():array
    {
        return array_keys(self::AREA);
    }

    public static function getZipCodeByCity(string $zipCode):string
    {
        $area = array_flip(self::AREA);
        Assertion::keyExists($area, $zipCode, 'sorry, zip code is not valid!');
        return $area[$zipCode];
    }

    public static function getCityByZipCode(string $city):string
    {
        Assertion::keyExists($city, $city, 'sorry, the city does not exist in germany');
        return self::AREA[$city];
    }

    public function __toString()
    {
        return 'the Address is in Germany, ' . $this->city . ' city, with zip code : ' . $this->zipCode;
    }
}
