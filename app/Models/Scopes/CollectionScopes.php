<?php

namespace App\Models\Scopes;

use App\Models\Collection;
use App\Models\Service;
use App\Models\ServiceTaxonomy;
use Illuminate\Database\Eloquent\Builder;

trait CollectionScopes
{
    /**
     * Get only category collections.
     */
    public function scopeCategories(Builder $query): Builder
    {
        return $query->where('type', Collection::TYPE_CATEGORY);
    }

    /**
     * Get only persona collections.
     */
    public function scopePersonas(Builder $query): Builder
    {
        return $query->where('type', Collection::TYPE_PERSONA);
    }

    /**
     * Get only organisation-event collections.
     */
    public function scopeOrganisationEvents(Builder $query): Builder
    {
        return $query->where('type', Collection::TYPE_ORGANISATION_EVENT);
    }

    public function scopeServices(Builder $query, Collection $collection): Builder
    {
        $taxonomyIds = $collection->collectionTaxonomies()->pluck('taxonomy_id')->toArray();
        $serviceIds = ServiceTaxonomy::query()->whereIn('taxonomy_id', $taxonomyIds)->pluck('service_id');

        return Service::query()->whereIn('id', $serviceIds);
    }
}
