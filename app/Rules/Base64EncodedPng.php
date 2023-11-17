<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class Base64EncodedPng implements ValidationRule
{
    /**
     * @var bool
     */
    protected $nullable;

    /**
     * Base64EncodedPng constructor.
     */
    public function __construct(bool $nullable = false)
    {
        $this->nullable = $nullable;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     * @param mixed $fail
     */
    public function validate(string $attribute, $value, $fail): void
    {
        if (!$this->nullable && $value === null) {
            $fail(__('validation.required'));
        }

        // Immediately fail if the value is not a string.
        if (!is_string($value)) {
            $fail(__('validation.string'));
        }

        if (!preg_match('/^(data:image\/png;base64,)/', $value)) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute field must be a Base64 encoded string of a PNG image.';
    }
}
