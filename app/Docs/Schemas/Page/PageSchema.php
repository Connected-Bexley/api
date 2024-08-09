<?php

namespace App\Docs\Schemas\Page;

use App\Docs\Schemas\Collection\Category\CollectionCategorySchema;
use App\Docs\Schemas\Collection\Persona\CollectionPersonaSchema;
use App\Models\Page;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PageSchema extends Schema
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
                Schema::object('image')
                    ->properties(
                        Schema::string('id'),
                        Schema::string('mime_type'),
                        Schema::string('alt_text'),
                        Schema::string('url')
                    )
                    ->nullable(),
                PageListItemSchema::create('landing_page'),
                PageListItemSchema::create('parent'),
                Schema::array('children')
                    ->items(PageListItemSchema::create()),
                Schema::array('ancestors')
                    ->items(PageListItemSchema::create()),
                Schema::array('collection_categories')
                    ->items(CollectionCategorySchema::create()),
                Schema::array('collection_personas')
                    ->items(CollectionPersonaSchema::create()),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
