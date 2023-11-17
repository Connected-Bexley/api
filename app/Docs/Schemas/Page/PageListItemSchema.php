<?php

namespace App\Docs\Schemas\Page;

use App\Docs\Schemas\File\FileSchema;
use App\Models\Page;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PageListItemSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::string('id')
                    ->format(Schema::FORMAT_UUID),
                Schema::string('slug'),
                Schema::string('title'),
                Schema::string('excerpt'),
                Schema::string('content'),
                Schema::integer('order'),
                Schema::boolean('enabled'),
                Schema::string('page_type')
                    ->enum(
                        Page::PAGE_TYPE_INFORMATION,
                        Page::PAGE_TYPE_LANDING
                    ),
                FileSchema::create('image'),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
