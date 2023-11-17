<?php

namespace App\Models;

use App\Models\Mutators\TaxonomyMutators;
use App\Models\Relationships\TaxonomyRelationships;
use App\Models\Scopes\TaxonomyScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class Taxonomy extends Model
{
    use HasFactory;
    use TaxonomyMutators;
    use TaxonomyRelationships;
    use TaxonomyScopes;

    const NAME_CATEGORY = 'Category';

    const NAME_ORGANISATION = 'Organisation';

    const NAME_SERVICE_ELIGIBILITY = 'Service Eligibility';

    public static function category(): self
    {
        return static::whereNull('parent_id')->where('name', static::NAME_CATEGORY)->firstOrFail();
    }

    public static function organisation(): self
    {
        return static::whereNull('parent_id')->where('name', static::NAME_ORGANISATION)->firstOrFail();
    }

    public static function serviceEligibility(): self
    {
        return static::whereNull('parent_id')->where('name', static::NAME_SERVICE_ELIGIBILITY)->firstOrFail();
    }

    public function getRootTaxonomy(Taxonomy $taxonomy = null): Taxonomy
    {
        $taxonomy = $taxonomy ?? $this;

        if ($taxonomy->parent_id === null) {
            return $taxonomy;
        }

        return $this->getRootTaxonomy($taxonomy->parent);
    }

    public function rootIsCalled(string $name): bool
    {
        return $this->getRootTaxonomy()->name === $name;
    }

    public function touchServices(): Taxonomy
    {
        $this->services()->get()->searchable();

        return $this;
    }

    protected function getDepth(): int
    {
        if ($this->parent_id === null) {
            return 0;
        }

        return 1 + $this->parent->getDepth();
    }

    public function updateDepth(): self
    {
        $this->update(['depth' => $this->getDepth()]);

        $this->children()->each(function (Taxonomy $child) {
            $child->updateDepth();
        });

        return $this;
    }

    /**
     * Return an array of all Taxonomies below the provided Taxonomy root.
     *
     * @param mixed $allTaxonomies
     */
    public function getAllDescendantTaxonomies(self $taxonomy, &$allTaxonomies = []): Collection
    {
        if (!$taxonomy) {
            $taxonomy = self::serviceEligibility();
        }

        if (is_array($allTaxonomies)) {
            $allTaxonomies = collect($allTaxonomies);
        }

        $allTaxonomies = $allTaxonomies->merge($taxonomy->children);

        foreach ($taxonomy->children as $childTaxonomy) {
            $this->getAllDescendantTaxonomies($childTaxonomy, $allTaxonomies);
        }

        return $allTaxonomies;
    }

    /**
     * Filter the passed taxonomy IDs for descendants of this taxonomy.
     *
     * @return array|bool
     */
    public function filterDescendants(array $taxonomyIds)
    {
        $descendantTaxonomyIds = $this
            ->getAllDescendantTaxonomies($this)
            ->pluck('id')
            ->toArray();
        $taxonomyIds = array_intersect($descendantTaxonomyIds, $taxonomyIds);

        return count($taxonomyIds) ? $taxonomyIds : false;
    }
}
