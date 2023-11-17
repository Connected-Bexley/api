<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait NotificationScopes
{
    public function scopeReferralId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('notifiable_type', 'referrals')
            ->whereIn('notifiable_id', $ids);
    }

    public function scopeServiceId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('notifiable_type', 'services')
            ->whereIn('notifiable_id', $ids);
    }

    public function scopeUserId(Builder $query, string $id): Builder
    {
        $ids = explode(',', $id);

        return $query
            ->where('notifiable_type', 'users')
            ->whereIn('notifiable_id', $ids);
    }
}
