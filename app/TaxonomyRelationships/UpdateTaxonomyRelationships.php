<?php

namespace App\TaxonomyRelationships;

use App\Models\Model;
use App\Models\Taxonomy;
use Illuminate\Support\Collection;

trait UpdateTaxonomyRelationships
{
    public function syncTaxonomyRelationships(Collection $taxonomies): Model
    {
        // Delete all existing taxonomy relationships
        $this->taxonomyRelationship()->delete();

        // Create a taxonomy relationship record for each taxonomy and their parents.
        foreach ($taxonomies as $taxonomy) {
            $this->createTaxonomyRelationships($taxonomy);
        }

        return $this;
    }

    protected function createTaxonomyRelationships(Taxonomy $taxonomy): Model
    {
        $hasParent = $taxonomy->parent !== null;
        $parentIsNotTopLevel = $hasParent && $taxonomy->parent->id !== Taxonomy::category()->id;

        if ($parentIsNotTopLevel) {
            $this->createTaxonomyRelationships($taxonomy->parent);
        }

        return $this->taxonomyRelationship()->updateOrCreate(['taxonomy_id' => $taxonomy->id]);
    }
}
