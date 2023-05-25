<?php

namespace App\Models;

use App\Models\Relationships\TagRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;
    use TagRelationships;

    /**
     * Mass assignable attributes.
     *
     * @var array
     */
    protected $fillable = ['slug', 'label'];
}
