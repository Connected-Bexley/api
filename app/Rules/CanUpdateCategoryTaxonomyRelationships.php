<?php

namespace App\Rules;

use App\Models\Organisation;
use App\Models\OrganisationEvent;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use App\TaxonomyRelationships\HasTaxonomyRelationships;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class CanUpdateCategoryTaxonomyRelationships implements ValidationRule
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var HasTaxonomyRelationships
     */
    protected $model;

    /**
     * Create a new rule instance.
     */
    public function __construct(User $user, HasTaxonomyRelationships $model)
    {
        $this->user = $user;
        $this->model = $model;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     * @param mixed $fail
     */
    public function validate(string $attribute, $value, $fail): void
    {
        // Immediately fail if the value is not an array of strings.
        if (!is_array($value)) {
            $fail(__('validation.array'));
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                $fail(':attribute must be an array of strings');
            }
        }

        // Allow changing of taxonomies if global admin.
        if (!($this->user->isSuperAdmin()
            || ($this->model instanceof Service && $this->user->isGlobalAdmin())
            || ($this->model instanceof Organisation && $this->user->isOrganisationAdmin($this->model))
            || ($this->model instanceof OrganisationEvent && $this->user->isOrganisationAdmin($this->model->organisation)))
        ) {

            // Only pass if the taxonomies remain unchanged.
            $existingTaxonomyIds = $this->model
                ->taxonomies()
                ->pluck(table(Taxonomy::class, 'id'))
                ->toArray();
            $existingTaxonomies = array_values(Arr::sort($existingTaxonomyIds));
            $newTaxonomies = array_values(Arr::sort($value));

            if ($existingTaxonomies !== $newTaxonomies) {
                $fail($this->message());
            }
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'You are not authorised to update this ' . class_basename($this->model) . '\'s category taxonomies.';
    }
}
