<?php

namespace App\Models\Scopes;

use App\Models\ServiceRefreshToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

trait ServiceRefreshTokenScopes
{
    public function scopeDueForDeletion(Builder $query): Builder
    {
        $date = Date::today()->subMonths(ServiceRefreshToken::AUTO_DELETE_MONTHS);

        return $query->where('created_at', '<=', $date);
    }
}
