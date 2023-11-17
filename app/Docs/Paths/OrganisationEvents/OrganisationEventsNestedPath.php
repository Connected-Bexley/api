<?php

namespace App\Docs\Paths\OrganisationEvents;

use App\Docs\Operations\OrganisationEvents\DestroyOrganisationEventOperation;
use App\Docs\Operations\OrganisationEvents\ShowOrganisationEventOperation;
use App\Docs\Operations\OrganisationEvents\UpdateOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class OrganisationEventsNestedPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisation-events/{organisation_event}')
            ->parameters(
                Parameter::path()
                    ->name('organisation_event')
                    ->description('The ID of the organisation event')
                    ->required()
                    ->schema(Schema::string()->format(Schema::FORMAT_UUID))
            )
            ->operations(
                ShowOrganisationEventOperation::create(),
                UpdateOrganisationEventOperation::create(),
                DestroyOrganisationEventOperation::create()
            );
    }
}
