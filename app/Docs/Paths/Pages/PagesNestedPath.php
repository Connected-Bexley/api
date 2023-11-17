<?php

namespace App\Docs\Paths\Pages;

use App\Docs\Operations\Pages\DestroyPageOperation;
use App\Docs\Operations\Pages\ShowPageOperation;
use App\Docs\Operations\Pages\UpdatePageOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PagesNestedPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/pages/{Page}')
            ->parameters(
                Parameter::path()
                    ->name('Page')
                    ->description('The ID or slug of the Page')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowPageOperation::create(),
                UpdatePageOperation::create(),
                DestroyPageOperation::create()
            );
    }
}
