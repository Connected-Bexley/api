<?php

namespace App\Models\Relationships;

use App\Models\Organisation;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OrganisationTaxonomyRelationships
{
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }
}
