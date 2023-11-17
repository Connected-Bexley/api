<?php

namespace App\Docs\Operations\Collections\Personas;

use App\Docs\Schemas\AllSchema;
use App\Docs\Schemas\Collection\Persona\CollectionPersonaSchema;
use App\Docs\Tags\CollectionPersonasTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class AllCollectionPersonaOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(CollectionPersonasTag::create())
            ->summary('List all the persona collections')
            ->description(
                <<<'EOT'
**Permission:** `Open`

---

Collections are returned in ascending order of the order field.
EOT
            )
            ->noSecurity()
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        AllSchema::create(null, CollectionPersonaSchema::create())
                    )
                )
            );
    }
}
