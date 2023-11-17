<?php

namespace App\Models\Relationships;

use App\Models\OrganisationEvent;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OrganisationEventTaxonomyRelationships
{
    public function organisationEvent(): BelongsTo
    {
        return $this->belongsTo(OrganisationEvent::class);
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }
}
