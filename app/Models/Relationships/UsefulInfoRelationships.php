<?php

namespace App\Models\Relationships;

use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait UsefulInfoRelationships
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
