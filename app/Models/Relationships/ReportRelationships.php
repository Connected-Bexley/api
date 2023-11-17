<?php

namespace App\Models\Relationships;

use App\Models\File;
use App\Models\ReportType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ReportRelationships
{
    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    /**
     * @return mixed
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
