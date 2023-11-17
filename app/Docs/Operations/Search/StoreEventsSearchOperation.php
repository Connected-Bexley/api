<?php

namespace App\Docs\Operations\Search;

use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Schemas\Location\LocationSchema;
use App\Docs\Schemas\OrganisationEvent\OrganisationEventSchema;
use App\Docs\Schemas\PaginationSchema;
use App\Docs\Schemas\Search\StoreEventSearchSchema;
use App\Docs\Schemas\ServiceLocation\ServiceLocationSchema;
use App\Docs\Tags\SearchTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreEventsSearchOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        $eventLocationSchema = ServiceLocationSchema::create();
        $eventLocationSchema = $eventLocationSchema->properties(
            LocationSchema::create('location'),
            ...$eventLocationSchema->properties
        );

        $eventSchema = OrganisationEventSchema::create();
        $eventSchema = $eventSchema->properties(
            Schema::object('location')->properties($eventLocationSchema),
            ...$eventSchema->properties
        );

        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(SearchTag::create())
            ->summary('Perform a search for events')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create()
            )
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreEventSearchSchema::create())
                    )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginationSchema::create(null, $eventSchema)
                    )
                )
            );
    }
}
