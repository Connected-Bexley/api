<?php

namespace App\Notifications;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Notifications
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
