<?php

namespace App\Docs\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Http\Response as LaravelResponse;

class JpegResponse extends Response
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->statusCode(LaravelResponse::HTTP_OK)
            ->description('OK')
            ->content(
                MediaType::jpeg()->schema(
                    Schema::string()->format(Schema::FORMAT_BINARY)
                )
            );
    }
}
