<?php

namespace App\Rules;

use App\Models\Organisation;
use App\Models\OrganisationEvent;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use App\TaxonomyRelationships\HasTaxonomyRelationships;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class CanUpdateCategoryTaxonomyRelationships implements Rule
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * @var \App\TaxonomyRelationships\HasTaxonomyRelationships
     */
    protected $model;

    /**
     * Create a new rule instance.
     *
     * @param \App\Models\User $user
     * @param \App\TaxonomyRelationships\HasTaxonomyRelationships $model
     */
    public function __construct(User $user, HasTaxonomyRelationships $model)
    {
        $this->user = $user;
        $this->model = $model;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Immediately fail if the value is not an array of strings.
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        // Allow changing of taxonomies if global admin.
        if ($this->user->isSuperAdmin()
            || ($this->model instanceof Service && $this->user->isGlobalAdmin())
            || ($this->model instanceof Organisation && $this->user->isOrganisationAdmin($this->model))
            || ($this->model instanceof OrganisationEvent && $this->user->isOrganisationAdmin($this->model->organisation))
        ) {
            return true;
        }

        // Only pass if the taxonomies remain unchanged.
        $existingTaxonomyIds = $this->model
            ->taxonomies()
            ->pluck(table(Taxonomy::class, 'id'))
            ->toArray();
        $existingTaxonomies = array_values(Arr::sort($existingTaxonomyIds));
        $newTaxonomies = array_values(Arr::sort($value));
        $taxonomiesUnchanged = $existingTaxonomies === $newTaxonomies;

        return $taxonomiesUnchanged;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You are not authorised to update this ' . class_basename($this->model) . '\'s category taxonomies.';
    }
}
