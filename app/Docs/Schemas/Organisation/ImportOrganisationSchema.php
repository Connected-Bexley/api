<?php

namespace App\Docs\Schemas\Organisation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportOrganisationSchema extends Schema
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
                    ->description('Base64 encoded string of an Excel compatible spreadsheet'),
                Schema::array('ignore_duplicates')->items(
                    Schema::string()
                        ->format(static::FORMAT_UUID)
                        ->description('Exisiting duplicate Organisation ID. Clashes in import will be ignored.')
                )
            );
    }
}
