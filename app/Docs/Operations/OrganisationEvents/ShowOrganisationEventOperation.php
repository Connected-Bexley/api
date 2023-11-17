<?php

namespace App\Docs\Operations\OrganisationEvents;

use App\Docs\Parameters\IncludeParameter;
use App\Docs\Schemas\OrganisationEvent\OrganisationEventSchema;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Tags\OrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(OrganisationEventsTag::create())
            ->summary('Get a specific organisation event')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                IncludeParameter::create(null, ['organisation'])
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, OrganisationEventSchema::create())
                    )
                )
            );
    }
}
