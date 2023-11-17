<?php

namespace App\Rules;

use App\Models\UpdateRequest;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserEmailNotInPendingSignupRequest implements ValidationRule
{
    /**
     * @var string|null
     */
    protected $message;

    /**
     * Create a new rule instance.
     *
     * @param \App\Models\User|null $excludedUser
     */
    public function __construct(string $message = null)
    {
        $this->message = $message;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $email
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.string'));
        }

        if (UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::NEW_TYPE_ORGANISATION_SIGN_UP_FORM)
            ->where('data->user->email', $value)
            ->exists()) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?? 'This email address exists for a user pending approval.';
    }
}
