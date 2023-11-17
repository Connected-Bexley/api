<?php

namespace App\Models\Relationships;

use App\Models\CollectionTaxonomy;
use App\Models\Page;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait CollectionRelationships
{
    public function collectionTaxonomies(): HasMany
    {
        return $this->hasMany(CollectionTaxonomy::class);
    }

    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, (new CollectionTaxonomy())->getTable());
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }
}
