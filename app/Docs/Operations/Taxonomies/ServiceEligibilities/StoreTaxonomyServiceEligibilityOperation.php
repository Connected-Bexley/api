<?php

namespace App\Docs\Operations\Taxonomies\ServiceEligibilities;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\Taxonomy\ServiceEligibility\StoreTaxonomyServiceEligibilitySchema;
use App\Docs\Schemas\Taxonomy\ServiceEligibility\TaxonomyServiceEligibilitySchema;
use App\Docs\Tags\TaxonomyServiceEligibilitiesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreTaxonomyServiceEligibilityOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(TaxonomyServiceEligibilitiesTag::create())
            ->summary('Create a service eligibility taxonomy')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreTaxonomyServiceEligibilitySchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, TaxonomyServiceEligibilitySchema::create())
                    )
                )
            );
    }
}
