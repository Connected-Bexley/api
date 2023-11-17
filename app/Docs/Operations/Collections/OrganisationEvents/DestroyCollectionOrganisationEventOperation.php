<?php

namespace App\Docs\Operations\Collections\OrganisationEvents;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Tags\CollectionOrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class DestroyCollectionOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_DELETE)
            ->tags(CollectionOrganisationEventsTag::create())
            ->summary('Delete a specific organisation event collection')
            ->description('**Permission:** `Super Admin`')
            ->responses(
                ResourceDeletedResponse::create(null, 'collection organisation event')
            );
    }
}
