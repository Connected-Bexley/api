<?php

namespace App\Rules;

use App\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;

class CollectionExists implements ValidationRule
{
    /**
     * @var string
     */
    protected $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     * @param mixed $fail
     */
    public function validate(string $attribute, $value, $fail): void
    {
        // Immediately fail if the value is not a string.
        if (!is_string($value)) {
            $fail(__('validation.string'));
        }

        if (Collection::query()
            ->where('type', '=', $this->type)
            ->where('slug', '=', $value)
            ->doesntExist()) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "The :attribute field must be a valid {$this->type} collection.";
    }
}
