<?php

namespace App\Docs\Paths\Pages;

use App\Docs\Operations\Pages\IndexPageOperation;
use App\Docs\Operations\Pages\StorePageOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class PagesRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/pages')
            ->operations(
                IndexPageOperation::create(),
                StorePageOperation::create()
            );
    }
}
