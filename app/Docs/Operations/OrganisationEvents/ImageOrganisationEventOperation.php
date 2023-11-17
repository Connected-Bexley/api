<?php

namespace App\Docs\Operations\OrganisationEvents;

use App\Docs\Parameters\MaxDimensionParameter;
use App\Docs\Parameters\UpdateRequestIdParameter;
use App\Docs\Responses\PngResponse;
use App\Docs\Tags\OrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class ImageOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(OrganisationEventsTag::create())
            ->summary("Get a specific organisation event's image")
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                MaxDimensionParameter::create(),
                UpdateRequestIdParameter::create()
            )
            ->responses(PngResponse::create());
    }
}
