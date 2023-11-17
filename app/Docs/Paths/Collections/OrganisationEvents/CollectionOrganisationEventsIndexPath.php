<?php

namespace App\Docs\Paths\Collections\OrganisationEvents;

use App\Docs\Operations\Collections\OrganisationEvents\IndexCollectionOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class CollectionOrganisationEventsIndexPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/collections/organisation-events/index')
            ->operations(
                IndexCollectionOrganisationEventOperation::create()
                    ->action(IndexCollectionOrganisationEventOperation::ACTION_POST)
            );
    }
}
