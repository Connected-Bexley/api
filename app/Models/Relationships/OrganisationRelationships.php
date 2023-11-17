<?php

namespace App\Models\Relationships;

use App\Models\File;
use App\Models\OrganisationTaxonomy;
use App\Models\Role;
use App\Models\Service;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait OrganisationRelationships
{
    public function logoFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, (new UserRole())->getTable())->withTrashed();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function socialMedias(): MorphMany
    {
        return $this->morphMany(SocialMedia::class, 'sociable');
    }

    public function nonAdminUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, (new UserRole())->getTable())
            ->withTrashed()
            ->whereDoesntHave('userRoles', function (Builder $query) {
                $query->whereIn('user_roles.role_id', [Role::superAdmin()->id, Role::globalAdmin()->id]);
            });
    }

    public function organisationTaxonomies(): HasMany
    {
        return $this->hasMany(OrganisationTaxonomy::class);
    }

    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, (new OrganisationTaxonomy())->getTable());
    }
}
