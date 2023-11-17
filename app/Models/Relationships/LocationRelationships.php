<?php

namespace App\Models\Relationships;

use App\Models\File;
use App\Models\Service;
use App\Models\ServiceLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait LocationRelationships
{
    public function serviceLocations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, (new ServiceLocation())->getTable());
    }

    public function imageFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_file_id');
    }
}
