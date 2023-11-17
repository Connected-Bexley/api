<?php

namespace App\Models\Relationships;

use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OfferingRelationships
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
