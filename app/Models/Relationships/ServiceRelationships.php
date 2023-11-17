<?php

namespace App\Models\Relationships;

use App\Models\File;
use App\Models\Location;
use App\Models\Offering;
use App\Models\Organisation;
use App\Models\Referral;
use App\Models\ServiceEligibility;
use App\Models\ServiceGalleryItem;
use App\Models\ServiceLocation;
use App\Models\ServiceRefreshToken;
use App\Models\ServiceTaxonomy;
use App\Models\SocialMedia;
use App\Models\Tag;
use App\Models\Taxonomy;
use App\Models\UsefulInfo;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ServiceRelationships
{
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function logoFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function serviceLocations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, (new ServiceLocation())->getTable());
    }

    public function socialMedias(): MorphMany
    {
        return $this->morphMany(SocialMedia::class, 'sociable');
    }

    public function usefulInfos(): HasMany
    {
        return $this->hasMany(UsefulInfo::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class)->orderBy('order', 'asc');
    }

    public function serviceTaxonomies(): HasMany
    {
        return $this->hasMany(ServiceTaxonomy::class);
    }

    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, (new ServiceTaxonomy())->getTable());
    }

    public function serviceEligibilities()
    {
        return $this->hasMany(ServiceEligibility::class);
    }

    public function eligibilities(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, (new ServiceEligibility())->getTable());
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, table(UserRole::class));
    }

    public function serviceGalleryItems(): HasMany
    {
        return $this->hasMany(ServiceGalleryItem::class);
    }

    public function serviceRefreshTokens(): HasMany
    {
        return $this->hasMany(ServiceRefreshToken::class);
    }

    /**
     * The tags that belong to the service.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
