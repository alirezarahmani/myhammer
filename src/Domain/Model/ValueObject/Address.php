<?php
namespace MyHammer\Domain\Model\ValueObject;

// Note: Address like City, Zip Code and Country names Can be value object
// Reference: https://stackoverflow.com/questions/1368977/ddd-should-country-be-a-value-object-or-an-entity
use MyHammer\Library\Assert\Assertion;

class Address implements ValueObject
{
    private $city;
    private $zipCode;

    private const AREA = [
        '10115' => 'Berlin',
        '32457' => 'Porta Westfalica',
        '01623' => 'Lommatzsch',
        '21521' => 'Hamburg',
        '06895' => 'Bülzig',
        '01612' => 'Diesbar-Seußlitz'
    ];

    public function __construct(string $city, int $zipCode)
    {
        $this->city = $city;
        $this->zipCode = $zipCode;
        Assertion::keyExists(self::AREA, $zipCode, 'german zipcode only');
        Assertion::inArray($city, self::AREA, ' german city only');
    }

    public function getZipCode():string
    {
        return $this->zipCode;
    }

    public function getCity():string
    {
        return $this->city;
    }

    public static function getGermanyZipCodes():array
    {
        return array_keys(self::AREA);
    }

    public static function getGermanyCities():array
    {
        return array_values(self::AREA);
    }

    public function __toString()
    {
        return 'is in Germany, ' . $this->city . ' city, with zip code : ' . $this->zipCode;
    }
}
