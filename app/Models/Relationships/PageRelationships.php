<?php

namespace App\Models\Relationships;

use App\Models\Collection;
use App\Models\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kalnoy\Nestedset\AncestorsRelation;

trait PageRelationships
{
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_file_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function collectionCategories(): BelongsToMany
    {
        return $this->collections()->where('type', Collection::TYPE_CATEGORY);
    }

    public function collectionPersonas(): BelongsToMany
    {
        return $this->collections()->where('type', Collection::TYPE_PERSONA);
    }

    public function landingPageAncestors(): AncestorsRelation
    {
        return $this->ancestors()->where('page_type', static::PAGE_TYPE_LANDING);
    }
}
