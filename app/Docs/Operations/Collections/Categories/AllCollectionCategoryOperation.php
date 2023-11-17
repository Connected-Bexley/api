<?php

namespace App\Docs\Operations\Collections\Categories;

use App\Docs\Schemas\AllSchema;
use App\Docs\Schemas\Collection\Category\CollectionCategorySchema;
use App\Docs\Tags\CollectionCategoriesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class AllCollectionCategoryOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(CollectionCategoriesTag::create())
            ->summary('List all the category collections')
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
                        AllSchema::create(null, CollectionCategorySchema::create())
                    )
                )
            );
    }
}
