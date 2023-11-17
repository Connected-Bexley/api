<?php

namespace App\Docs\Operations\Collections\OrganisationEvents;

use App\Docs\Responses\SvgResponse;
use App\Docs\Tags\CollectionOrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class ImageCollectionOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(CollectionOrganisationEventsTag::create())
            ->summary("Get a specific organisation event collection's image")
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(SvgResponse::create());
    }
}
