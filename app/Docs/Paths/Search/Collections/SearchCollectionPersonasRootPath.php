<?php

namespace App\Docs\Paths\Search\Collections;

use App\Docs\Operations\Search\Collections\StoreSearchCollectionPersonaOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class SearchCollectionPersonasRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/search/collections/personas')
            ->operations(
                StoreSearchCollectionPersonaOperation::create()
            );
    }
}
