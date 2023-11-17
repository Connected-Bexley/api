<?php

namespace App\Models\Scopes;

use App\Models\Location;
use App\Support\Coordinate;
use Illuminate\Database\Eloquent\Builder;

trait LocationScopes
{
    public function scopeOrderByDistance(Builder $query, Coordinate $location): Builder
    {
        $latColumn = table(Location::class, 'lat');
        $lonColumn = table(Location::class, 'lon');

        $sql = "(acos(
            cos(radians({$location->lat()})) * 
            cos(radians($latColumn)) * 
            cos(radians($lonColumn) - radians({$location->lon()})) + 
            sin(radians({$location->lat()})) * 
            sin(radians($latColumn)) 
        ))";

        return $query->orderByRaw($sql);
    }
}
