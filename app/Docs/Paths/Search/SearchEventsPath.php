<?php

namespace App\Docs\Paths\Search;

use App\Docs\Operations\Search\StoreEventsSearchOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class SearchEventsPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/search/events')
            ->operations(
                StoreEventsSearchOperation::create()
            );
    }
}
