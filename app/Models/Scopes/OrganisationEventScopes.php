<?php

namespace App\Models\Scopes;

use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\Location;
use App\Models\OrganisationEventTaxonomy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait OrganisationEventScopes
{
    public function scopeEndsAfter(Builder $query, string $dateString): Builder
    {
        return $query->whereDate('end_date', '>=', $dateString);
    }

    public function scopeEndsBefore(Builder $query, string $dateString): Builder
    {
        return $query->whereDate('end_date', '<=', $dateString);
    }

    public function scopeStartsAfter(Builder $query, string $dateString): Builder
    {
        return $query->whereDate('start_date', '>=', $dateString);
    }

    public function scopeStartsBefore(Builder $query, string $dateString): Builder
    {
        return $query->whereDate('start_date', '<=', $dateString);
    }

    public function scopeHasWheelchairAccess(Builder $query, bool $required): Builder
    {
        return $query->whereExists(function ($query) use ($required) {
            $locationsTable = (new Location())->getTable();
            $query->select(DB::raw(1))
                ->from($locationsTable)
                ->whereRaw("$locationsTable.id = {$this->getTable()}.location_id")
                ->where("$locationsTable.has_wheelchair_access", (bool)$required);
        });
    }

    public function scopeHasInductionLoop(Builder $query, bool $required): Builder
    {
        return $query->whereExists(function ($query) use ($required) {
            $locationsTable = (new Location())->getTable();
            $query->select(DB::raw(1))
                ->from($locationsTable)
                ->whereRaw("$locationsTable.id = {$this->getTable()}.location_id")
                ->where("$locationsTable.has_induction_loop", (bool)$required);
        });
    }

    public function scopeHasAccessibleToilet(Builder $query, bool $required): Builder
    {
        return $query->whereExists(function ($query) use ($required) {
            $locationsTable = (new Location())->getTable();
            $query->select(DB::raw(1))
                ->from($locationsTable)
                ->whereRaw("$locationsTable.id = {$this->getTable()}.location_id")
                ->where("$locationsTable.has_accessible_toilet", (bool)$required);
        });
    }

    /**
     * @param bool $required
     */
    public function scopeInCollections(Builder $query, ...$collectionIds): Builder
    {
        return $query->whereIn('id', function ($query) use ($collectionIds) {
            $organisationEventTaxonomyTable = (new OrganisationEventTaxonomy())->getTable();
            $collectionTaxonomyTable = (new CollectionTaxonomy())->getTable();
            $query->select("$organisationEventTaxonomyTable.organisation_event_id")
                ->from($organisationEventTaxonomyTable)
                ->join($collectionTaxonomyTable, "$collectionTaxonomyTable.taxonomy_id", '=', "$organisationEventTaxonomyTable.taxonomy_id")
                ->whereIn("$collectionTaxonomyTable.collection_id", $collectionIds);
        });
    }

    public function scopeCollectionTaxonomies(Builder $query): Builder
    {
        return $query->from((new CollectionTaxonomy())->getTable())->whereIn('taxonomy_id', function ($query) {
            $query->select('taxonomy_id')
                ->from((new OrganisationEventTaxonomy())->getTable())
                ->where('organisation_event_id', $this->id);
        });
    }

    /**
     * @param \App\Models\OrganisationEvent $organisationEvent
     */
    public function scopeCollections(Builder $query): Builder
    {
        return $query->from((new Collection())->getTable())
            ->where('type', Collection::TYPE_ORGANISATION_EVENT)
            ->whereIn('id', function ($query) {
                $query->from((new CollectionTaxonomy())->getTable())
                    ->whereIn('taxonomy_id', function ($query) {
                        $query->select('taxonomy_id')
                            ->from((new OrganisationEventTaxonomy())->getTable())
                            ->where('organisation_event_id', $this->id);
                    })->select('collection_id');
            });
    }
}
