<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class VideoEmbed implements ValidationRule
{
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

        $validDomains = [
            'https://www.youtube.com',
            'https://player.vimeo.com',
            'https://vimeo.com',
        ];

        if (!Str::startsWith($value, $validDomains)) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must be provided by either YouTube or Vimeo.';
    }
}
