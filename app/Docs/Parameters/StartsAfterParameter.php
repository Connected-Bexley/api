<?php

namespace App\Docs\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StartsAfterParameter extends Parameter
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->in(static::IN_QUERY)
            ->name('filter[starts_after]')
            ->description('The event start date is after the supplied date')
            ->schema(
                Schema::string()->format(Schema::FORMAT_DATE)->example('2022-05-11')
            );
    }
}
