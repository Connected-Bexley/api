<?php

namespace App\Models\Mutators;

trait SearchHistoryMutators
{
    public function getQueryAttribute(string $query): array
    {
        return json_decode($query, true);
    }

    public function setQueryAttribute(array $query)
    {
        $this->attributes['query'] = json_encode($query);
    }
}
