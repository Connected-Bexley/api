<?php

namespace App\Docs\Paths\Users\User;

use App\Docs\Operations\Users\User\DestroyUserSessionOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class UserSessionsPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/users/user/sessions')
            ->operations(
                DestroyUserSessionOperation::create()
            );
    }
}
