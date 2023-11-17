<?php

namespace App\Models\Relationships;

use App\Models\ServiceLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HolidayOpeningHourRelationships
{
    public function serviceLocation(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class);
    }
}
