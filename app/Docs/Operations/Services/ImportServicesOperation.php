<?php

namespace App\Docs\Operations\Services;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\Service\ImportServiceSchema;
use App\Docs\Schemas\Service\ImportServicesResponseSchema;
use App\Docs\Tags\ServicesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ImportServicesOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(ServicesTag::create())
            ->summary('Import services')
            ->description('**Permission:** `Super Admin` `Global Admin` `Organisation Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(ImportServiceSchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, ImportServicesResponseSchema::create())
                    )
                )
            );
    }
}
