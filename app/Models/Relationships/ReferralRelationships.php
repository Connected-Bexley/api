<?php

namespace App\Models\Relationships;

use App\Models\Service;
use App\Models\StatusUpdate;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait ReferralRelationships
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    public function organisationTaxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'organisation_taxonomy_id');
    }

    public function latestCompletedStatusUpdate(): HasOne
    {
        return $this->hasOne(StatusUpdate::class)
            ->orderByDesc(table(StatusUpdate::class, 'created_at'))
            ->where(table(StatusUpdate::class, 'to'), '=', StatusUpdate::TO_COMPLETED);
    }
}
