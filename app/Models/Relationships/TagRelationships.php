<?php

namespace App\Models\Relationships;

use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait TagRelationships
{
    /**
     * The services that belong to the tag.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }
}
