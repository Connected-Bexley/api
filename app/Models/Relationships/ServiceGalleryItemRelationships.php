<?php

namespace App\Models\Relationships;

use App\Models\File;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ServiceGalleryItemRelationships
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
