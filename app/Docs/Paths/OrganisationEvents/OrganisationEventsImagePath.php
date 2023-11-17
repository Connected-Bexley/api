<?php

namespace App\Docs\Paths\OrganisationEvents;

use App\Docs\Operations\OrganisationEvents\ImageOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class OrganisationEventsImagePath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisation-events/{organisation_event}/image.png')
            ->parameters(
                Parameter::path()
                    ->name('organisation_event')
                    ->description('The ID of the organisation event')
                    ->required()
                    ->schema(Schema::string()->format(Schema::FORMAT_UUID))
            )
            ->operations(
                ImageOrganisationEventOperation::create()
            );
    }
}
