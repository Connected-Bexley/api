<?php

namespace App\Docs\Paths\Taxonomies\Categories;

use App\Docs\Operations\Taxonomies\Categories\DestroyTaxonomyCategoryOperation;
use App\Docs\Operations\Taxonomies\Categories\ShowTaxonomyCategoryOperation;
use App\Docs\Operations\Taxonomies\Categories\UpdateTaxonomyCategoryOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class TaxonomyCategoriesNestedPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/taxonomies/categories/{category}')
            ->parameters(
                Parameter::path()
                    ->name('category')
                    ->description('The ID or slug of the category taxonomy')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowTaxonomyCategoryOperation::create(),
                UpdateTaxonomyCategoryOperation::create(),
                DestroyTaxonomyCategoryOperation::create()
            );
    }
}
