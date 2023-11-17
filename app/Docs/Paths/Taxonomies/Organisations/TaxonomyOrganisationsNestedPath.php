<?php

namespace App\Docs\Paths\Taxonomies\Organisations;

use App\Docs\Operations\Taxonomies\Organisations\DestroyTaxonomyOrganisationOperation;
use App\Docs\Operations\Taxonomies\Organisations\ShowTaxonomyOrganisationOperation;
use App\Docs\Operations\Taxonomies\Organisations\UpdateTaxonomyOrganisationOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class TaxonomyOrganisationsNestedPath extends PathItem
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/taxonomies/organisations/{organisation}')
            ->parameters(
                Parameter::path()
                    ->name('organisation')
                    ->description('The ID or slug of the organisation taxonomy')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowTaxonomyOrganisationOperation::create(),
                UpdateTaxonomyOrganisationOperation::create(),
                DestroyTaxonomyOrganisationOperation::create()
            );
    }
}
