<?php

namespace App\Docs\Operations\UpdateRequests;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Schemas\UpdateRequest\UpdateRequestRejectSchema;
use App\Docs\Tags\UpdateRequestsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;

class DestroyUpdateRequestOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(UpdateRequestsTag::create())
            ->summary('Delete a specific update request')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(UpdateRequestRejectSchema::create())
                    )
            )
            ->responses(
                ResourceDeletedResponse::create(null, 'update request')
            );
    }
}
