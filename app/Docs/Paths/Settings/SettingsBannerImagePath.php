<?php

namespace App\Docs\Paths\Settings;

use App\Docs\Operations\Settings\BannerImageSettingOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class SettingsBannerImagePath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/settings/banner-image.png')
            ->operations(
                BannerImageSettingOperation::create()
            );
    }
}
