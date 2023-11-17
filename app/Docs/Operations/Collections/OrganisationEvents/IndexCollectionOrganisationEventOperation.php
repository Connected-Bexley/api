<?php

namespace App\Docs\Operations\Collections\OrganisationEvents;

use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Schemas\Collection\OrganisationEvent\CollectionOrganisationEventSchema;
use App\Docs\Schemas\PaginationSchema;
use App\Docs\Tags\CollectionOrganisationEventsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class IndexCollectionOrganisationEventOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(CollectionOrganisationEventsTag::create())
            ->summary('List all the organisation event collections with pagination')
            ->description(
                <<<'EOT'
**Permission:** `Open`

---

Collections are returned in ascending order of the order field.
EOT
            )
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create()
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginationSchema::create(null, CollectionOrganisationEventSchema::create())
                    )
                )
            );
    }
}
