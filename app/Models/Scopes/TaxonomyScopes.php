<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait TaxonomyScopes
{
    public function scopeTopLevelCategories(Builder $query): Builder
    {
        return $query->where('parent_id', static::category()->id);
    }

    public function scopeOrganisations(Builder $query): Builder
    {
        return $query->where('parent_id', static::organisation()->id);
    }

    public function scopeTopLevelServiceEligibilities(Builder $query): Builder
    {
        return $query->where('parent_id', static::serviceEligibility()->id);
    }
}
