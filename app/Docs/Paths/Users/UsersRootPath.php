<?php

namespace App\Docs\Paths\Users;

use App\Docs\Operations\Users\IndexUserOperation;
use App\Docs\Operations\Users\StoreUserOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class UsersRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/users')
            ->operations(
                IndexUserOperation::create(),
                StoreUserOperation::create()
            );
    }
}
