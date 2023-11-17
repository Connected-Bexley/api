<?php

namespace App\Models\Mutators;

trait FileMutators
{
    public function getMetaAttribute(?string $meta): ?array
    {
        return ($meta === null) ? null : json_decode($meta, true);
    }

    public function setMetaAttribute(?array $meta)
    {
        $this->attributes['meta'] = ($meta === null) ? null : json_encode($meta);
    }
}
