<?php

namespace App\Models;

use App\Models\Mutators\TaxonomyMutators;
use App\Models\Relationships\TaxonomyRelationships;
use App\Models\Scopes\TaxonomyScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Taxonomy extends Model
{
    use HasFactory;
    use TaxonomyMutators;
    use TaxonomyRelationships;
    use TaxonomyScopes;

    const NAME_CATEGORY = 'Category';

    const NAME_ORGANISATION = 'Organisation';

    const NAME_SERVICE_ELIGIBILITY = 'Service Eligibility';

    /**
     * @return \App\Models\Taxonomy
     */
    public static function category(): self
    {
        return static::whereNull('parent_id')->where('name', static::NAME_CATEGORY)->firstOrFail();
    }

    /**
     * @return \App\Models\Taxonomy
     */
    public static function organisation(): self
    {
        return static::whereNull('parent_id')->where('name', static::NAME_ORGANISATION)->firstOrFail();
    }

    /**
     * @return \App\Models\Taxonomy
     */
    public static function serviceEligibility(): self
    {
        return static::whereNull('parent_id')->where('name', static::NAME_SERVICE_ELIGIBILITY)->firstOrFail();
    }

    /**
     * @param \App\Models\Taxonomy|null $taxonomy
     * @return \App\Models\Taxonomy
     */
    public function getRootTaxonomy(Taxonomy $taxonomy = null): Taxonomy
    {
        $taxonomy = $taxonomy ?? $this;

        if ($taxonomy->parent_id === null) {
            return $taxonomy;
        }

        return $this->getRootTaxonomy($taxonomy->parent);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function rootIsCalled(string $name): bool
    {
        return $this->getRootTaxonomy()->name === $name;
    }

    /**
     * @return \App\Models\Taxonomy
     */
    public function touchServices(): Taxonomy
    {
        $this->services()->get()->each->save();

        return $this;
    }

    /**
     * @return int
     */
    protected function getDepth(): int
    {
        if ($this->parent_id === null) {
            return 0;
        }

        return 1 + $this->parent->getDepth();
    }

    /**
     * @return self
     */
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
     * @param App\Models\Taxonomy $taxonomy
     * @param mixed $allTaxonomies
     * @return Illuminate\Support\Collection
     */
    public function getAllDescendantTaxonomies(self $taxonomy, &$allTaxonomies = [])
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
     * @param array $taxonomyIds
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
