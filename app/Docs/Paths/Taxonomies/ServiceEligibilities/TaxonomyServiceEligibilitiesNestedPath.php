<?php

namespace App\Docs\Paths\Taxonomies\ServiceEligibilities;

use App\Docs\Operations\Taxonomies\ServiceEligibilities\DestroyTaxonomyServiceEligibilityOperation;
use App\Docs\Operations\Taxonomies\ServiceEligibilities\ShowTaxonomyServiceEligibilityOperation;
use App\Docs\Operations\Taxonomies\ServiceEligibilities\UpdateTaxonomyServiceEligibilityOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class TaxonomyServiceEligibilitiesNestedPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/taxonomies/service-eligibilities/{eligibility}')
            ->parameters(
                Parameter::path()
                    ->name('eligibility')
                    ->description('The ID or slug of the eligibility taxonomy')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowTaxonomyServiceEligibilityOperation::create(),
                UpdateTaxonomyServiceEligibilityOperation::create(),
                DestroyTaxonomyServiceEligibilityOperation::create()
            );
    }
}
