<?php

namespace App\Docs\Operations\OrganisationEvents;

use App\Docs\Responses\UpdateRequestReceivedResponse;
use App\Docs\Schemas\OrganisationEvent\StoreOrganisationEventSchema;
use App\Docs\Tags\OrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;

class StoreOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(OrganisationEventsTag::create())
            ->summary('Create a organisation event')
            ->description('**Permission:** `Organisation Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreOrganisationEventSchema::create())
                    )
            )
            ->responses(
                UpdateRequestReceivedResponse::create(null, StoreOrganisationEventSchema::create())
            );
    }
}
