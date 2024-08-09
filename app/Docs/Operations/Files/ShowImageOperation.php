<?php

namespace App\Docs\Operations\Files;

use App\Docs\Parameters\MaxDimensionParameter;
use App\Docs\Responses\JpegResponse;
use App\Docs\Responses\PngResponse;
use App\Docs\Responses\SvgResponse;
use App\Docs\Tags\FilesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class ShowImageOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(FilesTag::create())
            ->summary('Display an image file')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                MaxDimensionParameter::create(),
            )
            ->responses(
                JpegResponse::create(),
                PngResponse::create(),
                SvgResponse::create()
            );
    }
}
