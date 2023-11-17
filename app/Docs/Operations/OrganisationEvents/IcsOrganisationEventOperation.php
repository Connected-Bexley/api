<?php

namespace App\Docs\Operations\OrganisationEvents;

use App\Docs\Responses\IcsResponse;
use App\Docs\Tags\OrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class IcsOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(OrganisationEventsTag::create())
            ->summary("Get a specific organisation event's ics file")
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(IcsResponse::create());
    }
}
