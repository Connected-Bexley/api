<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

class UserEmailNotTaken implements ValidationRule
{
    /**
     * @var User|null
     */
    protected $excludedUser;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * Create a new rule instance.
     */
    public function __construct(User $excludedUser = null, string $message = null)
    {
        $this->excludedUser = $excludedUser;
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

        if (User::query()
            ->where('email', $value)
            ->when($this->excludedUser, function (Builder $query): Builder {
                return $query->where('id', '!=', $this->excludedUser->id);
            })
            ->exists()) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?? 'This email address has already been taken.';
    }
}
