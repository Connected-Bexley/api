<?php

namespace App\UpdateRequest;

use App\Models\UpdateRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait UpdateRequests
{
    public function updateRequests(): MorphMany
    {
        return $this->morphMany(UpdateRequest::class, 'updateable');
    }
}
