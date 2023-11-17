<?php

namespace App\Docs\Paths\Services;

use App\Docs\Operations\Services\IndexServiceOperation;
use App\Docs\Operations\Services\StoreServiceOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class ServicesRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/services')
            ->operations(
                IndexServiceOperation::create(),
                StoreServiceOperation::create()
            );
    }
}
