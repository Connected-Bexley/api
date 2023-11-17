<?php

namespace App\Docs\Tags;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Tag;

class TagsTag extends Tag
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->name('Tags');
    }
}
