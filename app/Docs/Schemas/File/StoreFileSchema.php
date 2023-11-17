<?php

namespace App\Docs\Schemas\File;

use App\Models\File;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreFileSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'is_private',
                'mime_type',
                'file'
            )
            ->properties(
                Schema::boolean('is_private'),
                Schema::string('mime_type')
                    ->enum(
                        File::MIME_TYPE_PNG,
                        File::MIME_TYPE_JPG,
                        File::MIME_TYPE_JPEG,
                        File::MIME_TYPE_SVG
                    ),
                Schema::string('file')
                    ->format(static::FORMAT_BINARY)
                    ->description('Base64 encoded string of the image')
            );
    }
}
