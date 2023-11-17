<?php

namespace App\Models\Mutators;

trait UpdateRequestMutators
{
    public function getDataAttribute(string $data): array
    {
        return json_decode($data, true);
    }

    public function setDataAttribute(array $data)
    {
        $this->attributes['data'] = json_encode($data);
    }
}
