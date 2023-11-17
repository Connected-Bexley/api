<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Password implements ValidationRule
{
    const ALLOWED_SPECIAL_CHARACTERS = '!#$%&()*+,-./:;<=>?@[]^_`{|}~';

    /**
     * @var string|null
     */
    protected $message;

    /**
     * Password constructor.
     */
    public function __construct(string $message = null)
    {
        $this->message = $message;
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

        if (preg_match($this->regex(), $value) === 0) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?? 'The :attribute must be at least eight characters long, contain one uppercase letter, one lowercase letter, one number and one special character (' . static::ALLOWED_SPECIAL_CHARACTERS . ').';
    }

    /**
     * Returns the regex for the password.
     */
    protected function regex(): string
    {
        return "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[{$this->escapedSpecialCharacters()}])[A-Za-z\d{$this->escapedSpecialCharacters()}]{8,}/";
    }

    /**
     * Returns the special characters escaped for the regex.
     */
    protected function escapedSpecialCharacters(): string
    {
        $characters = mb_str_split(static::ALLOWED_SPECIAL_CHARACTERS);

        return collect($characters)
            ->map(function (string $character) {
                return '\\' . $character;
            })
            ->implode('');
    }
}
