<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait PageScopes
{
    /**
     * Get the InformationPage sibling at given index.
     */
    public function scopeSiblingAtIndex(Builder $query, int $index): Builder
    {
        return $this->siblingsAndSelf()->defaultOrder()->offset($index)->limit(1);
    }

    /**
     * Get the Landing Page descendants.
     */
    public function scopePageDescendants(Builder $query, string $uuid): Builder
    {
        $descendantIds = static::descendantsOf($uuid)->pluck('id');

        return $query->whereIn('id', $descendantIds)->orderBy('_lft');
    }
}
