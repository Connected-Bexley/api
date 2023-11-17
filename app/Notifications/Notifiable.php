<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Notifiable
{
    public function notifications(): MorphMany;
}
