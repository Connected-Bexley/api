<?php

namespace App\Docs\Schemas\Service;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportServiceSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'spreadsheet'
            )
            ->properties(
                Schema::string('spreadsheet')
                    ->format(static::FORMAT_BINARY)
                    ->description('Base64 encoded string of an Excel compatible spreadsheet')
            );
    }
}
