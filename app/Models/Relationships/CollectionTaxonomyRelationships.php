<?php

namespace App\Models\Relationships;

use App\Models\Collection;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CollectionTaxonomyRelationships
{
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }
}
