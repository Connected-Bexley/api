<?php

namespace App\Docs\Operations\UpdateRequests;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\UpdateRequest\UpdateRequestSchema;
use App\Docs\Tags\UpdateRequestsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ApproveUpdateRequestOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(UpdateRequestsTag::create())
            ->summary('Approve a specific update request')
            ->description('**Permission:** `Super Admin`')
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, UpdateRequestSchema::create())
                    )
                )
            );
    }
}
