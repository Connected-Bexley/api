<?php

namespace App\Docs\Operations\Taxonomies\ServiceEligibilities;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\Taxonomy\ServiceEligibility\TaxonomyServiceEligibilitySchema;
use App\Docs\Tags\TaxonomyServiceEligibilitiesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowTaxonomyServiceEligibilityOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(TaxonomyServiceEligibilitiesTag::create())
            ->summary('Get a specific service eligibility taxonomy')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, TaxonomyServiceEligibilitySchema::create())
                    )
                )
            );
    }
}
