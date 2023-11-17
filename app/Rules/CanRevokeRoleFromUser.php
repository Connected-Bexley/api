<?php

namespace App\Rules;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;

class CanRevokeRoleFromUser implements ValidationRule
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * @var \App\Models\User
     */
    protected $subject;

    /**
     * @var array|null
     */
    protected $revokedRoles;

    /**
     * CanAssignRoleToUser constructor.
     */
    public function __construct(User $user, User $subject, array $revokedRoles = null)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->revokedRoles = $revokedRoles;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $role
     * @param mixed $fail
     */
    public function validate(string $attribute, $role, $fail): void
    {
        // Immediately fail if the value is not an array.
        if (!$this->validateRole($role)) {
            $fail(':attribute must be an array with a role key and if required a service_id or organisation_id key');
        }

        // Skip if the role is not provided in the revoked roles array.
        if (!$this->shouldSkip($role)) {

            switch ($role['role']) {
                case Role::NAME_SERVICE_WORKER:
                    $service = Service::query()->findOrFail($role['service_id']);
                    if (!$this->user->canRevokeServiceWorker($this->subject, $service)) {
                        $fail($this->message('Service Worker'));
                    }
                    break;
                case Role::NAME_SERVICE_ADMIN:
                    $service = Service::query()->findOrFail($role['service_id']);
                    if (!$this->user->canRevokeServiceAdmin($this->subject, $service)) {
                        $fail($this->message('Service Admin'));
                    }
                    break;
                case Role::NAME_ORGANISATION_ADMIN:
                    $organisation = Organisation::query()->findOrFail($role['organisation_id']);
                    if (!$this->user->canRevokeOrganisationAdmin($this->subject, $organisation)) {
                        $fail($this->message('Organisation Admin'));
                    }
                    break;
                case Role::NAME_CONTENT_ADMIN:
                    if (!$this->user->canRevokeContentAdmin($this->subject)) {
                        $fail($this->message('Content Admin'));
                    }
                    break;
                case Role::NAME_GLOBAL_ADMIN:
                    if (!$this->user->canRevokeGlobalAdmin($this->subject)) {
                        $fail($this->message('Global Admin'));
                    }
                    break;
                case Role::NAME_SUPER_ADMIN:
                    if (!$this->user->canRevokeSuperAdmin($this->subject)) {
                        $fail($this->message('Super Admin'));
                    }
                    break;
            }

        }
    }

    /**
     * Get the validation error message.
     * @param mixed $role
     */
    public function message($role): string
    {
        return "You are unauthorised to revoke $role roles for this user.";
    }

    /**
     * Validates the value.
     * @param mixed $role
     */
    protected function validateRole($role): bool
    {
        // check if array.
        if (!is_array($role)) {
            return false;
        }

        // check if role key provided.
        if (!isset($role['role'])) {
            return false;
        }

        // Check if service_id or organisation_id provided (for certain roles).
        switch ($role['role']) {
            case Role::NAME_SERVICE_WORKER:
            case Role::NAME_SERVICE_ADMIN:
                if (!isset($role['service_id']) || !is_string($role['service_id'])) {
                    return false;
                }
                break;
            case Role::NAME_ORGANISATION_ADMIN:
                if (!isset($role['organisation_id']) || !is_string($role['organisation_id'])) {
                    return false;
                }
                break;
        }

        return true;
    }

    protected function shouldSkip(array $role): bool
    {
        // If no revoked roles where provided then don't skip.
        if ($this->revokedRoles === null) {
            return false;
        }

        $revokedRoles = $this->parseRoles($this->revokedRoles);
        $role = $this->parseRoles($role);

        // If revoked role provided, and the role is in the array, then don't skip.
        foreach ($revokedRoles as $revokedRole) {
            if ($revokedRole == $role) {
                return false;
            }
        }

        // If revoked roles provided, but the role is not in the array, then skip.
        return true;
    }

    protected function parseRoles(array $roles): array
    {
        $rolesCopy = isset($roles['role']) ? [$roles] : $roles;

        foreach ($rolesCopy as &$role) {
            switch ($role['role']) {
                case Role::NAME_ORGANISATION_ADMIN:
                    unset($role['service_id']);
                    break;
                case Role::NAME_CONTENT_ADMIN:
                case Role::NAME_GLOBAL_ADMIN:
                case Role::NAME_SUPER_ADMIN:
                    unset($role['service_id'], $role['organisation_id']);

                    break;
            }
        }

        return isset($roles['role']) ? $rolesCopy[0] : $rolesCopy;
    }
}
