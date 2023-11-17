<?php

namespace App\Docs\Operations\Services;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\Service\ServiceSchema;
use App\Docs\Schemas\Service\StoreServiceSchema;
use App\Docs\Tags\ServicesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreServiceOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(ServicesTag::create())
            ->summary('Create a service')
            ->description('**Permission:** `Organisation Admin` The HTTP response code is `200 OK` if an
            update request is created and must be approved for the changes to take place,
            or `201 Created` if the service was created immediately (only for Super Admins)')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreServiceSchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, ServiceSchema::create())
                    )
                ),
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, ServiceSchema::create())
                    )
                )
            );
    }
}
