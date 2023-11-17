<?php

namespace App\Docs\Paths\OrganisationEvents;

use App\Docs\Operations\OrganisationEvents\IndexOrganisationEventOperation;
use App\Docs\Operations\OrganisationEvents\StoreOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class OrganisationEventsRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisation-events')
            ->operations(
                IndexOrganisationEventOperation::create(),
                StoreOrganisationEventOperation::create()
            );
    }
}
