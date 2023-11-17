<?php

namespace App\Models\Relationships;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait StatusUpdateRelationships
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }
}
