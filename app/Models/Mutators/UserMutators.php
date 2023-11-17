<?php

namespace App\Models\Mutators;

trait UserMutators
{
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
