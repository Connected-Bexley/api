<?php

namespace App\Http\Filters\UpdateRequest;

use App\Models\UpdateRequest;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class TypeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        if ($value === UpdateRequest::NEW_TYPE_SERVICE_ORG_ADMIN || $value == UpdateRequest::NEW_TYPE_SERVICE_GLOBAL_ADMIN) {
            $value = [
                UpdateRequest::NEW_TYPE_SERVICE_ORG_ADMIN,
                UpdateRequest::NEW_TYPE_SERVICE_GLOBAL_ADMIN,
            ];
        }

        return $query->whereIn('updateable_type', (array)$value);
    }
}
