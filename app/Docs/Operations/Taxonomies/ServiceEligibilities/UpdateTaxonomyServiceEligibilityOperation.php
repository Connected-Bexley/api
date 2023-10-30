<?php

namespace App\Docs\Operations\Taxonomies\ServiceEligibilities;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\Taxonomy\ServiceEligibility\TaxonomyServiceEligibilitySchema;
use App\Docs\Schemas\Taxonomy\ServiceEligibility\UpdateTaxonomyServiceEligibilitySchema;
use App\Docs\Tags\TaxonomyServiceEligibilitiesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class UpdateTaxonomyServiceEligibilityOperation extends Operation
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
            ->tags(TaxonomyServiceEligibilitiesTag::create())
            ->summary('Update a specific service eligibility taxonomy')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(UpdateTaxonomyServiceEligibilitySchema::create())
                    )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, TaxonomyServiceEligibilitySchema::create())
                    )
                )
            );
    }
}
