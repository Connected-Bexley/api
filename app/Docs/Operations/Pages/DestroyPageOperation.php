<?php

namespace App\Docs\Operations\Pages;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Tags\PagesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class DestroyPageOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_DELETE)
            ->tags(PagesTag::create())
            ->summary('Delete a specific information page')
            ->description('**Permission:** `Super Admin`')
            ->responses(
                ResourceDeletedResponse::create(null, 'information page')
            );
    }
}
