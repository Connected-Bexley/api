<?php

namespace App\Docs\Paths\Pages;

use App\Docs\Operations\Pages\ImagePageOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PagesImagePath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/pages/{Page}/image.png')
            ->parameters(
                Parameter::path()
                    ->name('Page')
                    ->description('The ID or slug of the Page')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ImagePageOperation::create()
            );
    }
}
