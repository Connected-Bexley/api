<?php

namespace App\Docs\Paths\Files;

use App\Docs\Operations\Files\ShowImageOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImagesNestedPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/images/{filename}?max_dimension={max_dimension}')
            ->parameters(
                Parameter::path()
                    ->name('filename')
                    ->description('The name of the file')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowImageOperation::create(),
            );
    }
}
