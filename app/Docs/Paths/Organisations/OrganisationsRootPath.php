<?php

namespace App\Docs\Paths\Organisations;

use App\Docs\Operations\Organisations\IndexOrganisationOperation;
use App\Docs\Operations\Organisations\StoreOrganisationOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class OrganisationsRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisations')
            ->operations(
                IndexOrganisationOperation::create(),
                StoreOrganisationOperation::create()
            );
    }
}
