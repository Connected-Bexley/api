<?php

namespace App\Docs\Operations\Taxonomies\ServiceEligibilities;

use App\Docs\Schemas\AllSchema;
use App\Docs\Schemas\Taxonomy\ServiceEligibility\TaxonomyServiceEligibilitySchema;
use App\Docs\Tags\TaxonomyServiceEligibilitiesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class IndexTaxonomyServiceEligibilityOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(TaxonomyServiceEligibilitiesTag::create())
            ->summary('List all the service eligibility taxonomies')
            ->description(
                <<<'EOT'
**Permission:** `Open`

---

Taxonomies are returned in ascending order of the order field.
EOT
            )
            ->noSecurity()
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        AllSchema::create(null, TaxonomyServiceEligibilitySchema::create())
                    )
                )
            );
    }
}
