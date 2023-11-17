<?php

namespace App\Rules;

use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;

class IsServiceAdmin implements ValidationRule
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * Create a new rule instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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

        $service = Service::query()->find($value);

        if (!$service || !$this->user->isServiceAdmin($service)) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute field must contain an ID for a service you are a service admin for.';
    }
}
