<?php

namespace App\Support;

class Address
{
    /**
     * @var string
     */
    public $addressLine1;

    /**
     * @var string|null
     */
    public $addressLine2;

    /**
     * @var string|null
     */
    public $addressLine3;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $county;

    /**
     * @var string
     */
    public $postcode;

    /**
     * @var string
     */
    public $country;

    /**
     * Address constructor.
     * @param mixed $address
     */
    public function __construct($address, string $city, string $county, string $postcode, string $country)
    {
        $this->addressLine1 = (array)$address[0];
        $this->addressLine2 = (array)$address[1] ?? null;
        $this->addressLine3 = (array)$address[2] ?? null;
        $this->city = $city;
        $this->county = $county;
        $this->postcode = $postcode;
        $this->country = $country;
    }

    public static function create($address, string $city, string $county, string $postcode, string $country): Address
    {
        return new static($address, $city, $county, $postcode, $country);
    }

    /**
     * Return the address data as a string.
     */
    public function __toString(): string
    {
        return implode(', ', array_map(function ($value) {
            return is_array($value) ? $value[0] : $value;
        }, array_filter([
            $this->addressLine1,
            $this->addressLine2,
            $this->addressLine3,
            $this->city,
            $this->county,
            $this->postcode,
            $this->country,
        ], function ($value) {
            return (bool)$value;
        })));
    }
}
