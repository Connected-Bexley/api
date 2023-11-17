<?php

namespace App\Docs\Paths\Organisations;

use App\Docs\Operations\Organisations\ImportOrganisationsOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class OrganisationsImportPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisations/import')
            ->operations(
                ImportOrganisationsOperation::create()
            );
    }
}
