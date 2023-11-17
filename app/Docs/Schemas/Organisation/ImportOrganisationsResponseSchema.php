<?php

namespace App\Docs\Schemas\Organisation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportOrganisationsResponseSchema extends Schema
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::integer('imported_row_count'),
                Schema::object('errors')->properties(
                    Schema::array('spreadsheet')->items(
                        Schema::object()->properties(
                            Schema::object('row')->properties(
                                Schema::integer('index'),
                                Schema::string('id'),
                                Schema::string('name'),
                                Schema::string('description'),
                                Schema::string('url'),
                                Schema::string('email'),
                                Schema::string('phone')
                            ),
                            Schema::object('errors')->properties(
                                Schema::string('id'),
                                Schema::string('name'),
                                Schema::string('description'),
                                Schema::string('url'),
                                Schema::string('email'),
                                Schema::string('phone')
                            )
                        )
                    )
                ),
                Schema::array('duplicates')->items(
                    Schema::object()->properties(
                        Schema::object('row')->properties(
                            Schema::integer('index'),
                            Schema::string('id'),
                            Schema::string('name'),
                            Schema::string('description'),
                            Schema::string('url'),
                            Schema::string('email'),
                            Schema::string('phone')
                        ),
                        Schema::array('originals')->items(
                            Schema::object('row')->properties(
                                Schema::string('id'),
                                Schema::string('name'),
                                Schema::string('description'),
                                Schema::string('url'),
                                Schema::string('email'),
                                Schema::string('phone')
                            )
                        )
                    )
                )
            );
    }
}
