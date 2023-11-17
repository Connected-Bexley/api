<?php

namespace App\Models;

use App\Models\Mutators\UserRoleMutators;
use App\Models\Relationships\UserRoleRelationships;
use App\Models\Scopes\UserRoleScopes;

class UserRole extends Model
{
    use UserRoleMutators;
    use UserRoleRelationships;
    use UserRoleScopes;

    public function isServiceWorker(Service $service = null): bool
    {
        $isServiceAdmin = $this->role->name === Role::NAME_SERVICE_WORKER;

        return $service
        ? ($isServiceAdmin && $this->service_id === $service->id)
        : $isServiceAdmin;
    }

    public function isServiceAdmin(Service $service = null): bool
    {
        $isServiceAdmin = $this->role->name === Role::NAME_SERVICE_ADMIN;

        return $service
        ? ($isServiceAdmin && $this->service_id === $service->id)
        : $isServiceAdmin;
    }

    public function isOrganisationAdmin(Organisation $organisation = null): bool
    {
        $isOrganisationAdmin = $this->role->name === Role::NAME_ORGANISATION_ADMIN;

        return $organisation
        ? ($isOrganisationAdmin && $this->organisation_id === $organisation->id)
        : $isOrganisationAdmin;
    }

    public function isContentAdmin(): bool
    {
        return $this->role->name === Role::NAME_CONTENT_ADMIN;
    }

    public function isGlobalAdmin(): bool
    {
        return $this->role->name === Role::NAME_GLOBAL_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role->name === Role::NAME_SUPER_ADMIN;
    }
}
