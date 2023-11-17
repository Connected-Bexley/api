<?php

namespace App\Docs\Paths\Collections\OrganisationEvents;

use App\Docs\Operations\Collections\OrganisationEvents\ImageCollectionOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class CollectionOrganisationEventsImagePath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/collections/organisation-events/{collection}/image.svg')
            ->parameters(
                Parameter::path()
                    ->name('collection')
                    ->description('The ID of the organisation event collection')
                    ->required()
                    ->schema(Schema::string()->format(Schema::FORMAT_UUID))
            )
            ->operations(
                ImageCollectionOrganisationEventOperation::create()
            );
    }
}
