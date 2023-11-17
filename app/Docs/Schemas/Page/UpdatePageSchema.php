<?php

namespace App\Docs\Schemas\Page;

use App\Models\Page;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdatePageSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'title',
                'content'
            )
            ->properties(
                Schema::string('page_type')
                    ->enum(
                        Page::PAGE_TYPE_INFORMATION,
                        Page::PAGE_TYPE_LANDING
                    ),
                Schema::string('slug'),
                Schema::string('title'),
                Schema::string('excerpt'),
                Schema::string('content')->format('JSON'),
                Schema::string('parent_id'),
                Schema::integer('order'),
                Schema::boolean('enabled'),
                Schema::string('image_file_id'),
                Schema::array('collections')
                    ->items(
                        Schema::string()
                            ->format(Schema::FORMAT_UUID)
                    )
            );
    }
}
