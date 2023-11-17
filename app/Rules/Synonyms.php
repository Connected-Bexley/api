<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class Synonyms implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $synonym) {
            if (!is_string($synonym)) {
                $fail(__('validation.string'));
            }
        }

        if (preg_match('/\s/', Arr::last($value))) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The last synonym must be a single word.';
    }
}
