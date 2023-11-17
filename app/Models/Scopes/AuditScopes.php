<?php

namespace App\Models\Scopes;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

trait AuditScopes
{
    public function scopeDueForDeletion(Builder $query): Builder
    {
        $date = Date::today()->subMonths(Audit::AUTO_DELETE_MONTHS);

        return $query->where('updated_at', '<=', $date);
    }
}
