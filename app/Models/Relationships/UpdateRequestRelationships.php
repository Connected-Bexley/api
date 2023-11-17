<?php

namespace App\Models\Relationships;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait UpdateRequestRelationships
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function actioningUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioning_user_id')->withTrashed();
    }

    public function updateable(): MorphTo
    {
        return $this->morphTo('updateable');
    }
}
