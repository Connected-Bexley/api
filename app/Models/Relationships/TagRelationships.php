<?php

namespace App\Models\Relationships;

use App\Models\Service;

trait TagRelationships
{
    /**
     * The services that belong to the tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany(Service::class);
    }
}
