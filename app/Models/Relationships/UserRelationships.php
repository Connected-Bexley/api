<?php

namespace App\Models\Relationships;

use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\StatusUpdate;
use App\Models\UpdateRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait UserRelationships
{
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, (new UserRole())->getTable())->distinct();
    }

    /**
     * This returns a collection of the roles assigned to the user
     * ordered by the highest role first.
     */
    public function orderedRoles(): BelongsToMany
    {
        $sql = (new User())->getHighestRoleOrderSql();

        return $this->roles()->orderByRaw($sql['sql'], $sql['bindings']);
    }

    public function updateRequests(): HasMany
    {
        return $this->hasMany(UpdateRequest::class);
    }

    public function actionedUpdateRequests(): HasMany
    {
        return $this->hasMany(UpdateRequest::class, 'actioning_user_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function statusUpdated(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class, table(UserRole::class))
            ->distinct();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, table(UserRole::class))
            ->distinct();
    }
}
