<?php

namespace App\Models\Mutators;

trait CollectionMutators
{
    public function getMetaAttribute(string $meta): array
    {
        return json_decode($meta, true);
    }

    public function setMetaAttribute(array $meta)
    {
        $this->attributes['meta'] = json_encode($meta);
    }
}
