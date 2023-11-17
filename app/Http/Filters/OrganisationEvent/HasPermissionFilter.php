<?php

namespace App\Http\Filters\OrganisationEvent;

use App\Models\Organisation;
use App\Models\OrganisationEvent;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasPermissionFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $organisationIds = [];
        $user = request()->user('api');

        if ($user && $user->isOrganisationAdmin()) {
            $organisationIds = $user->organisations()
                ->pluck(table(Organisation::class, 'id'))
                ->toArray();
        }

        return $query->whereIn(table(OrganisationEvent::class, 'organisation_id'), $organisationIds);
    }
}
