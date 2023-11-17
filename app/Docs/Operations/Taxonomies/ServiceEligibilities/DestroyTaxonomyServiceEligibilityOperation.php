<?php

namespace App\Docs\Operations\Taxonomies\ServiceEligibilities;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Tags\TaxonomyServiceEligibilitiesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class DestroyTaxonomyServiceEligibilityOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_DELETE)
            ->tags(TaxonomyServiceEligibilitiesTag::create())
            ->summary('Delete a specific service eligibility taxonomy')
            ->description('**Permission:** `Global Admin`')
            ->responses(
                ResourceDeletedResponse::create(null, 'taxonomy service eligibility')
            );
    }
}
