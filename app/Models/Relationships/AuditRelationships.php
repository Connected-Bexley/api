<?php

namespace App\Models\Relationships;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Client;

trait AuditRelationships
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function oauthClient(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
