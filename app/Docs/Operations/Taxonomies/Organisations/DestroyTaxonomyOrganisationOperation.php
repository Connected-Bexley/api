<?php

namespace App\Docs\Operations\Taxonomies\Organisations;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Tags\TaxonomyOrganisationsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class DestroyTaxonomyOrganisationOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_DELETE)
            ->tags(TaxonomyOrganisationsTag::create())
            ->summary('Delete a specific organisation taxonomy')
            ->description('**Permission:** `Super Admin`')
            ->responses(
                ResourceDeletedResponse::create(null, 'taxonomy organisation')
            );
    }
}
