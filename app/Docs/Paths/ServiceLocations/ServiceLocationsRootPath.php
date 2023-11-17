<?php

namespace App\Docs\Paths\ServiceLocations;

use App\Docs\Operations\ServiceLocations\IndexServiceLocationOperation;
use App\Docs\Operations\ServiceLocations\StoreServiceLocationOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class ServiceLocationsRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/service-locations')
            ->operations(
                IndexServiceLocationOperation::create(),
                StoreServiceLocationOperation::create()
            );
    }
}
