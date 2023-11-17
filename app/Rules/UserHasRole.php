<?php

namespace App\Rules;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserHasRole implements ValidationRule
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * @var \App\Models\Role
     */
    protected $userRole;

    /**
     * @var mixed
     */
    protected $originalValue;

    /**
     * Create a new rule instance.
     *
     * @param mixed $originalValue
     */
    public function __construct(User $user, UserRole $userRole, $originalValue)
    {
        $this->user = $user;
        $this->userRole = $userRole;
        $this->originalValue = $originalValue;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->originalValue !== $value) {
            switch ($this->userRole->role->name) {
                case Role::NAME_SERVICE_WORKER:
                    $userHasRole = $this->user->isServiceWorker($this->userRole->service);
                    break;
                case Role::NAME_SERVICE_ADMIN:
                    $userHasRole = $this->user->isServiceWorker($this->userRole->service);
                    break;
                case Role::NAME_ORGANISATION_ADMIN:
                    $userHasRole = $this->user->isOrganisationAdmin($this->userRole->organisation);
                    break;
                case Role::NAME_CONTENT_ADMIN:
                    $userHasRole = $this->user->isContentAdmin();
                    break;
                case Role::NAME_GLOBAL_ADMIN:
                    $userHasRole = $this->user->isGlobalAdmin();
                    break;
                case Role::NAME_SUPER_ADMIN:
                    $userHasRole = $this->user->isSuperAdmin();
                    break;
                default:
                    $userHasRole = false;
            }

            if (!$userHasRole) {
                $fail($this->message());
            }
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'You are not authorised to update the :attribute field.';
    }
}
