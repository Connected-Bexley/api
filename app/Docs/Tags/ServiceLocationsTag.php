<?php

namespace App\Docs\Tags;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Tag;

class ServiceLocationsTag extends Tag
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->name('Service Locations');
    }
}
