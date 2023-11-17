<?php

namespace App\Docs\Paths\OrganisationEvents;

use App\Docs\Operations\OrganisationEvents\IndexOrganisationEventOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class OrganisationEventsIndexPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisation-events/index')
            ->operations(
                IndexOrganisationEventOperation::create()
                    ->action(IndexOrganisationEventOperation::ACTION_POST)
                    ->description(
                        <<<'EOT'
This is an alias of `GET /organisation-events` which allows all the query string parameters to be
passed as part of the request body.
EOT
                    )
            );
    }
}
