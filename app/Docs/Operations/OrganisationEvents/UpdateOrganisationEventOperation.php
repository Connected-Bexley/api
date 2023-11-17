<?php

namespace App\Docs\Operations\OrganisationEvents;

use App\Docs\Responses\UpdateRequestReceivedResponse;
use App\Docs\Schemas\OrganisationEvent\UpdateOrganisationEventSchema;
use App\Docs\Tags\OrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;

class UpdateOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(OrganisationEventsTag::create())
            ->summary('Update a specific organisation event')
            ->description('**Permission:** `Organisation Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(UpdateOrganisationEventSchema::create())
                    )
            )
            ->responses(
                UpdateRequestReceivedResponse::create(null, UpdateOrganisationEventSchema::create())
            );
    }
}
