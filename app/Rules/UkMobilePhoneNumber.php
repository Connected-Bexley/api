<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UkMobilePhoneNumber implements ValidationRule
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

        $matches = preg_match('/^(07[0-9]{9})$/', $value);

        if ($matches !== 1) {

            $matches = preg_match('/^(\+447[0-9]{9})$/', $value);

            if ($matches !== 1) {
                $fail($this->message());
            }

        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?? 'The :attribute must be a valid UK mobile phone number.';
    }
}
