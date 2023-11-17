<?php

namespace App\Docs\Operations\Locations;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Tags\LocationsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class DestroyLocationOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_DELETE)
            ->tags(LocationsTag::create())
            ->summary('Delete a specific location')
            ->description('**Permission:** `Super Admin`')
            ->responses(ResourceDeletedResponse::create());
    }
}
