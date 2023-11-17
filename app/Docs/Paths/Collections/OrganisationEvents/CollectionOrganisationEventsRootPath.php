<?php

namespace App\Docs\Paths\Collections\OrganisationEvents;

use App\Docs\Operations\Collections\OrganisationEvents\IndexCollectionOrganisationEventOperation;
use App\Docs\Operations\Collections\OrganisationEvents\StoreCollectionOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class CollectionOrganisationEventsRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/collections/organisation-events')
            ->operations(
                IndexCollectionOrganisationEventOperation::create(),
                StoreCollectionOrganisationEventOperation::create()
            );
    }
}
