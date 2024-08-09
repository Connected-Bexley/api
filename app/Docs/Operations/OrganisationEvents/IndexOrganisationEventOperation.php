<?php

namespace App\Docs\Operations\OrganisationEvents;

use App\Docs\Parameters\EndsAfterParameter;
use App\Docs\Parameters\EndsBeforeParameter;
use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\FilterParameter;
use App\Docs\Parameters\IncludeParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Parameters\StartsAfterParameter;
use App\Docs\Parameters\StartsBeforeParameter;
use App\Docs\Schemas\OrganisationEvent\OrganisationEventSchema;
use App\Docs\Schemas\PaginationSchema;
use App\Docs\Tags\OrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class IndexOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(OrganisationEventsTag::create())
            ->summary('List all the organisation events')
            ->description(
                <<<'EOT'
**Permission:** `Open`

---

Organisation events are returned in descending order of their start_date.
EOT
            )
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create(),
                StartsBeforeParameter::create(),
                StartsAfterParameter::create(),
                EndsBeforeParameter::create(),
                EndsAfterParameter::create(),
                FilterParameter::create(null, 'has_wheelchair_access')
                    ->description('Has a wheelchair accessible location')
                    ->schema(
                        Schema::boolean()
                    ),
                FilterParameter::create(null, 'has_induction_loop')
                    ->description('Has a location with an induction loop')
                    ->schema(
                        Schema::boolean()
                    ),
                FilterParameter::create(null, 'has_accessible_toilet')
                    ->description('Has a location with an accessible toilet')
                    ->schema(
                        Schema::boolean()
                    ),
                FilterParameter::create(null, 'organisation_id')
                    ->description('Comma separated list of organisation IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'collections')
                    ->description('Comma separated list of collection IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'has_permission')
                    ->description('Does the organisation admin user have permission to view')
                    ->schema(
                        Schema::boolean()
                    ),
                IncludeParameter::create(null, ['organisation'])
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginationSchema::create(null, OrganisationEventSchema::create())
                    )
                )
            );
    }
}
