<?php

namespace App\Docs\Schemas\UpdateRequest;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateRequestRejectSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'message'
            )
            ->properties(
                Schema::string('message'),
            );
    }
}
