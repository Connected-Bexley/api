<?php

namespace App\Models\Mutators;

use App\Models\File;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait CollectionMutators
{
    /**
     * Get the related image File.
     *
     * @return App\Models\File
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $meta = json_decode($attributes['meta']);

                return isset($meta->image_file_id) ? File::find($meta->image_file_id) : null;
            }
        );
    }
}
