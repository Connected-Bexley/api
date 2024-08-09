<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UkPhoneNumber implements ValidationRule
{
    /**
     * @var string|null
     */
    protected $message;

    /**
     * UkPhoneNumber constructor.
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

        if (preg_match('/^0([1-6][0-9]{8,10}|7[0-9]{9}|8[0-9]{9})$/', $value) !== 1) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?? 'The :attribute must be a valid UK phone number.';
    }
}
