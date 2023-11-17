<?php

namespace App\Docs\Paths\Taxonomies\ServiceEligibilities;

use App\Docs\Operations\Taxonomies\ServiceEligibilities\IndexTaxonomyServiceEligibilityOperation;
use App\Docs\Operations\Taxonomies\ServiceEligibilities\StoreTaxonomyServiceEligibilityOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class TaxonomyServiceEligibilitiesRootPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/taxonomies/service-eligibilities')
            ->operations(
                IndexTaxonomyServiceEligibilityOperation::create(),
                StoreTaxonomyServiceEligibilityOperation::create()
            );
    }
}
