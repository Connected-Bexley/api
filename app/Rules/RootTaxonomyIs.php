<?php

namespace App\Rules;

use App\Models\Taxonomy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RootTaxonomyIs implements ValidationRule
{
    /**
     * @var string
     */
    protected $rootTaxonomyName;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $rootTaxonomyName)
    {
        $this->rootTaxonomyName = $rootTaxonomyName;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Immediately fail if the value is not a string.
        if (!is_string($value)) {
            $fail(__('validation.string'));
        }

        $taxonomy = Taxonomy::query()->find($value);

        if (!$taxonomy || !$taxonomy->rootIsCalled($this->rootTaxonomyName)) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "The root taxonomy must be called [{$this->rootTaxonomyName}].";
    }
}
