<?php

namespace App\Models\Mutators;

use App\Models\File;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait FileMutators
{
    // public function getMetaAttribute(?string $meta): ?array
    // {
    //     return ($meta === null) ? null : json_decode($meta, true);
    // }

    // public function setMetaAttribute(?array $meta)
    // {
    //     $this->attributes['meta'] = ($meta === null) ? null : json_encode($meta);
    // }

    /**
     * Get the pending_assignment meta value.
     *
     * @return bool
     */
    protected function pendingAssignment(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $meta = json_decode($attributes['meta']);

                return $meta->type ? $meta->type === File::META_TYPE_PENDING_ASSIGNMENT : false;
            }
        );
    }

    /**
     * Get the alt_text meta value.
     *
     * @return string
     */
    protected function altText(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $meta = json_decode($attributes['meta']);

                return $meta->alt_text ?? null;
            }
        );
    }
}
