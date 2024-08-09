<?php

namespace App\Docs\Schemas\File;

use App\Models\File;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class FileSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::string('id')
                    ->format(Schema::FORMAT_UUID),
                Schema::boolean('is_private'),
                Schema::string('mime_type')
                    ->enum(
                        File::MIME_TYPE_PNG,
                        File::MIME_TYPE_JPG,
                        File::MIME_TYPE_JPEG,
                        File::MIME_TYPE_SVG
                    ),
                Schema::string('alt_text')
                    ->nullable(),
                Schema::string('max_dimension')
                    ->format(Schema::FORMAT_INT32)
                    ->nullable(),
                Schema::string('src')
                    ->format(static::FORMAT_BINARY)
                    ->description('Base64 encoded string of the image'),
                Schema::string('url')
                    ->format(Schema::TYPE_STRING)
                    ->description('The URL of the image file'),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
