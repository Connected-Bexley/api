<?php

namespace App\Docs\Operations\Pages;

use App\Docs\Responses\UpdateRequestReceivedResponse;
use App\Docs\Schemas\Page\UpdatePageSchema;
use App\Docs\Tags\PagesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;

class UpdatePageOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(PagesTag::create())
            ->summary('Update an information page')
            ->description('**Permission:** `Content Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(
                            UpdatePageSchema::create()
                        )
                    )
            )
            ->responses(
                UpdateRequestReceivedResponse::create(null, UpdatePageSchema::create())
            );
    }
}
