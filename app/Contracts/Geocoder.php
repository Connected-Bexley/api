<?php

namespace App\Contracts;

use App\Support\Address;
use App\Support\Coordinate;

interface Geocoder
{
    /**
     * Convert a a textual address into a coordinate.
     */
    public function geocode(Address $address): Coordinate;
}
