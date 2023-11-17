<?php

namespace App\Docs\Operations\Notifications;

use App\Docs\Schemas\Notification\NotificationSchema;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Tags\NotificationsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowNotificationOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(NotificationsTag::create())
            ->summary('Get a specific notification')
            ->description('**Permission:** `Super Admin`')
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, NotificationSchema::create())
                    )
                )
            );
    }
}
