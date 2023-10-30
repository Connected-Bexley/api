<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

class UserEmailNotTaken implements Rule
{
    /**
     * @var \App\Models\User|null
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
    public function passes(string $attribute, $email): bool
    {
        if (!is_string($email)) {
            return false;
        }

        return User::query()
            ->where('email', $email)
            ->when($this->excludedUser, function (Builder $query): Builder {
                return $query->where('id', '!=', $this->excludedUser->id);
            })
            ->doesntExist();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?? 'This email address has already been taken.';
    }
}
