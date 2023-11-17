<?php

namespace App\Docs\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class AllSchema extends Schema
{
    public static function create(string $objectId = null, Schema $schema = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::array('data')->items($schema)
            );
    }
}
