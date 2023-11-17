<?php

namespace App\Docs\Operations\Collections\OrganisationEvents;

use App\Docs\Schemas\Collection\OrganisationEvent\CollectionOrganisationEventSchema;
use App\Docs\Schemas\Collection\OrganisationEvent\StoreCollectionOrganisationEventSchema;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Tags\CollectionOrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreCollectionOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(CollectionOrganisationEventsTag::create())
            ->summary('Create an organisation event collection')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(
                            StoreCollectionOrganisationEventSchema::create()
                        )
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, CollectionOrganisationEventSchema::create())
                    )
                )
            );
    }
}
