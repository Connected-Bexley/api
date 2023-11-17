<?php

namespace App\Docs\Operations\Pages;

use App\Docs\Responses\UpdateRequestReceivedResponse;
use App\Docs\Schemas\Page\StorePageSchema;
use App\Docs\Tags\PagesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;

class StorePageOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(PagesTag::create())
            ->summary('Create an information page')
            ->description('**Permission:** `Content Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(
                            StorePageSchema::create()
                        )
                    )
            )
            ->responses(
                UpdateRequestReceivedResponse::create(null, StorePageSchema::create())
            );
    }
}
