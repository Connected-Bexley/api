<?php

namespace App\Docs;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\ExternalDocs as BaseExternalDocs;

class ExternalDocs extends BaseExternalDocs
{
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->description('GitHub Wiki')
            ->url('https://github.com/LondonBoroughSutton/helpyourselfsutton-api/wiki');
    }
}
