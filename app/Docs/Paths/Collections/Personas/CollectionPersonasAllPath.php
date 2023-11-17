<?php

namespace App\Docs\Paths\Collections\Personas;

use App\Docs\Operations\Collections\Personas\AllCollectionPersonaOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class CollectionPersonasAllPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/collections/personas/all')
            ->operations(
                AllCollectionPersonaOperation::create()
            );
    }
}
