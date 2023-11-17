<?php

namespace App\Docs\Paths\StatusUpdates;

use App\Docs\Operations\StatusUpdates\IndexStatusUpdateOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class StatusUpdatesRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/status-updates')
            ->operations(
                IndexStatusUpdateOperation::create()
            );
    }
}
