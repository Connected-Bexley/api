<?php

namespace App\TaxonomyRelationships;

use App\Models\Model;
use App\Models\Taxonomy;
use Illuminate\Support\Collection;

trait UpdateServiceEligibilityTaxonomyRelationships
{
    public function syncEligibilityRelationships(Collection $taxonomies): Model
    {
        // Delete all existing taxonomy relationships
        $this->serviceEligibilities()->delete();

        $taxonomyIds = $taxonomies->map(function ($taxonomyModel) {
            return ['taxonomy_id' => $taxonomyModel->id];
        })->toArray();

        $this->serviceEligibilities()->createMany($taxonomyIds);

        return $this;
    }

    protected function createEligibilityRelationship(Taxonomy $taxonomy): Model
    {
        return $this->serviceEligibilities()->updateOrCreate(['taxonomy_id' => $taxonomy->id]);
    }
}
