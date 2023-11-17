<?php

namespace App\Geocode;

use App\Support\Address;
use App\Support\Coordinate;

class StubGeocoder extends Geocoder
{
    /**
     * Convert a a textual address into a coordinate.
     */
    public function geocode(Address $address): Coordinate
    {
        // If this is a test for the AddressNotFoundException
        if ($address->postcode === 'xx12 3xx') {
            throw new AddressNotFoundException($this->normaliseAddress($address));
        }

        // Return coordinates for Leeds, UK.
        return new Coordinate(mt_rand(-90, 90), mt_rand(-180, 180));
    }
}
