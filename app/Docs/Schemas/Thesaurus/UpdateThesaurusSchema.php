<?php

namespace App\Docs\Schemas\Thesaurus;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateThesaurusSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required('synonyms')
            ->properties(
                Schema::array()->items(
                    Schema::array()->items(
                        Schema::string()
                    )
                )
            );
    }
}
