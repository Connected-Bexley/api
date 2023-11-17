<?php

namespace App\Models\Relationships;

use App\Models\ReportType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ReportScheduleRelationships
{
    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }
}
