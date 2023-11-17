<?php

namespace App\Docs\Paths\Search;

use App\Docs\Operations\Search\StorePagesSearchOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class SearchPagesPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/search/pages')
            ->operations(
                StorePagesSearchOperation::create()
            );
    }
}
