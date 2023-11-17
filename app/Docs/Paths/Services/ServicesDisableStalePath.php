<?php

namespace App\Docs\Paths\Services;

use App\Docs\Operations\Services\DisableStaleServiceOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class ServicesDisableStalePath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/services/disable-stale')
            ->operations(
                DisableStaleServiceOperation::create()
            );
    }
}
