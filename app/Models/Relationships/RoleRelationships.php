<?php

namespace App\Models\Relationships;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait RoleRelationships
{
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, (new UserRole())->getTable())->withTrashed();
    }
}
