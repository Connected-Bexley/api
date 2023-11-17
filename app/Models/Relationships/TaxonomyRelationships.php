<?php

namespace App\Models\Relationships;

use App\Models\CollectionTaxonomy;
use App\Models\OrganisationEvent;
use App\Models\OrganisationEventTaxonomy;
use App\Models\Referral;
use App\Models\Service;
use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TaxonomyRelationships
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Taxonomy::class, 'parent_id')->orderBy('order');
    }

    public function collectionTaxonomies(): HasMany
    {
        return $this->hasMany(CollectionTaxonomy::class);
    }

    public function serviceTaxonomies(): HasMany
    {
        return $this->hasMany(ServiceTaxonomy::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'organisation_taxonomy_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, (new ServiceTaxonomy())->getTable());
    }

    public function organisationEvents(): BelongsToMany
    {
        return $this->belongsToMany(OrganisationEvent::class, (new OrganisationEventTaxonomy())->getTable());
    }
}
