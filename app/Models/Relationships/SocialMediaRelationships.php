<?php

namespace App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\MorphTo;

trait SocialMediaRelationships
{
    public function sociable(): MorphTo
    {
        return $this->morphTo();
    }
}
