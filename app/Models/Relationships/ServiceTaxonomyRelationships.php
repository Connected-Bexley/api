<?php

namespace App\Models\Relationships;

use App\Models\Service;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ServiceTaxonomyRelationships
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }
}
