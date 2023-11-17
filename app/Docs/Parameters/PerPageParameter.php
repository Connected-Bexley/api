<?php

namespace App\Docs\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PerPageParameter extends Parameter
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->in(static::IN_QUERY)
            ->name('per_page')
            ->description('The number of items to load per page')
            ->schema(
                Schema::integer()
                    ->minimum(1)
                    ->maximum((int)config('local.max_pagination_results'))
                    ->default((int)config('local.pagination_results'))
            );
    }
}
