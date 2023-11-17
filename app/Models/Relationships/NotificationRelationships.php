<?php

namespace App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\MorphTo;

trait NotificationRelationships
{
    public function notifiable(): MorphTo
    {
        return $this->morphTo('notifiable');
    }
}
