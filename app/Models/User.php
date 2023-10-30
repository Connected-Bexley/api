<?php

namespace App\Models;

use App\Emails\Email;
use App\Emails\PasswordReset\UserEmail;
use App\Exceptions\CannotRevokeRoleException;
use App\Models\Mutators\UserMutators;
use App\Models\Relationships\UserRelationships;
use App\Models\Scopes\UserScopes;
use App\Notifications\Notifiable;
use App\Notifications\Notifications;
use App\Sms\Sms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements Notifiable
{
    use HasFactory;
    use DispatchesJobs;
    use HasApiTokens;
    use Notifications;
    use SoftDeletes;
    use UserMutators;
    use UserRelationships;
    use UserScopes;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Determines if the primary key is a UUID.
     *
     * @var bool
     */
    protected $keyIsUuid = true;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->perPage = config('local.pagination_results');
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = uuid();
            }
        });
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasAppend(string $name): bool
    {
        return in_array($name, $this->appends);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->sendEmail(new UserEmail($this->email, [
            'PASSWORD_RESET_LINK' => route('password.reset', ['token' => $token]),
        ]));
    }

    /**
     * @param \App\Models\Role $role
     * @param \App\Models\Service|null $service
     * @param \App\Models\Organisation|null $organisation
     * @return bool
     */
    protected function hasRole(Role $role, Service $service = null, Organisation $organisation = null): bool
    {
        if ($service !== null && $organisation !== null) {
            throw new InvalidArgumentException('A role cannot be assigned to both a service and an organisation');
        }

        return $this->userRoles()
            ->where('role_id', $role->id)
            ->when($service, function (Builder $query) use ($service) {
                return $query->where('service_id', $service->id);
            })
            ->when($organisation, function (Builder $query) use ($organisation) {
                return $query->where('organisation_id', $organisation->id);
            })
            ->exists();
    }

    /**
     * This method is functionally the same as hasRole(), however this uses the
     * userRoles relationship as a collection, so it's more efficient when this
     * relationship has been eager loaded. This can also cause caching issues
     * where the userRoles might be out of date if they have been modified.
     *
     * @param \App\Models\Role $role
     * @param \App\Models\Service|null $service
     * @param \App\Models\Organisation|null $organisation
     * @return bool
     */
    public function hasRoleCached(Role $role, Service $service = null, Organisation $organisation = null): bool
    {
        if ($service !== null && $organisation !== null) {
            throw new InvalidArgumentException('A role cannot be assigned to both a service and an organisation');
        }

        return $this->userRoles
            ->where('role_id', $role->id)
            ->when($service, function (Collection $collection) use ($service) {
                return $collection->where('service_id', $service->id);
            })
            ->when($organisation, function (Collection $collection) use ($organisation) {
                return $collection->where('organisation_id', $organisation->id);
            })
            ->isNotEmpty();
    }

    /**
     * @param \App\Models\Role $role
     * @param \App\Models\Service|null $service
     * @param \App\Models\Organisation|null $organisation
     * @return \App\Models\User
     */
    protected function assignRole(Role $role, Service $service = null, Organisation $organisation = null): self
    {
        if ($service !== null && $organisation !== null) {
            throw new InvalidArgumentException('A role cannot be assigned to both a service and an organisation');
        }

        // Check if the user already has the role.
        if ($this->hasRole($role, $service, $organisation)) {
            return $this;
        }

        // Create the role.
        UserRole::create([
            'user_id' => $this->id,
            'role_id' => $role->id,
            'service_id' => $service->id ?? null,
            'organisation_id' => $organisation->id ?? null,
        ]);

        return $this;
    }

    /**
     * @param \App\Models\Role $role
     * @param \App\Models\Service|null $service
     * @param \App\Models\Organisation|null $organisation
     * @return \App\Models\User
     */
    protected function removeRoll(Role $role, Service $service = null, Organisation $organisation = null): self
    {
        if ($service !== null && $organisation !== null) {
            throw new InvalidArgumentException('A role cannot be assigned to both a service and an organisation');
        }

        // Check if the user doesn't already have the role.
        if (!$this->hasRole($role)) {
            return $this;
        }

        // Remove the role.
        $this->userRoles()
            ->where('user_roles.role_id', $role->id)
            ->where('user_roles.service_id', $service->id ?? null)
            ->where('user_roles.organisation_id', $organisation->id ?? null)
            ->delete();

        return $this;
    }

    /**
     * Performs a check to see if the current user instance (invoker) can revoke a role on the subject.
     * This is an extremely important algorithm for user management.
     * This algorithm does not care about the exact role the invoker is trying to revoke on the subject.
     * All that matters is that the subject is not higher up than the invoker in the ACL hierarchy.
     *
     * @param \App\Models\User $subject
     * @param \App\Models\Organisation|null $organisation
     * @param \App\Models\Service|null $service
     * @return bool
     */
    protected function canRevokeRole(
        User $subject,
        Organisation $organisation = null,
        Service $service = null
    ): bool {
        // If the invoker is a super admin.
        if ($this->isSuperAdmin()) {
            return true;
        }

        /*
         * If the invoker is a global admin,
         */
        if ($this->isGlobalAdmin()) {
            return false;
        }

        /*
         * If the invoker is a content admin.
         */
        if ($this->isContentAdmin()) {
            return false;
        }

        /*
         * If the invoker is an organisation admin for the organisation,
         * and the subject is not a global admin.
         */
        if ($organisation && $this->isOrganisationAdmin($organisation) && !($subject->isSuperAdmin() || $subject->isGlobalAdmin() || $subject->isContentAdmin())) {
            return true;
        }

        /*
         * If the invoker is a service admin for the service,
         * and the subject is not a organisation admin for the organisation.
         */
        if ($service && $this->isServiceAdmin($service) && !$subject->isOrganisationAdmin($organisation)) {
            return true;
        }

        return false;
    }

    /**
     * Performs a check to see if the current user instance (invoker) can update the subject.
     * This is an extremely important algorithm for user management.
     * This algorithm does not care about the exact role the invoker is trying to revoke on the subject.
     * All that matters is that the subject is not higher up than the invoker in the ACL hierarchy.
     *
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canUpdate(User $subject): bool
    {
        /*
         * If the invoker is also the subject, i.e. the user is updating
         * their own account.
         */
        if ($this->id === $subject->id) {
            return true;
        }

        // If the invoker is a super admin.
        if ($this->isSuperAdmin()) {
            return true;
        }

        /*
         * If the invoker is a global admin,
         */
        if ($this->isGlobalAdmin()) {
            return false;
        }

        /*
         * If the invoker is a content admin,
         */
        if ($this->isContentAdmin()) {
            return false;
        }

        /*
         * If the invoker is an organisation admin for the organisation,
         * and the subject is not a content admin or a global admin.
         */
        if ($this->isOrganisationAdmin() && !($subject->isSuperAdmin() || $subject->isGlobalAdmin() || $subject->isContentAdmin())) {
            return true;
        }

        /*
         * If the invoker is a service admin for the service,
         * and the subject is not a organisation admin for the organisation.
         */
        if ($this->isServiceAdmin() && !($subject->isSuperAdmin() || $subject->isGlobalAdmin() || $subject->isContentAdmin() || $subject->isOrganisationAdmin())) {
            return true;
        }

        return false;
    }

    /**
     * Check if this user can view the record of another user.
     *
     * @param \App\Models\User
     * @return bool
     */
    public function canView(User $user)
    {
        return $this->visibleUserIds()->contains($user->id);
    }

    /**
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canDelete(User $subject): bool
    {
        return $this->canUpdate($subject);
    }

    /**
     * @param \App\Models\Service|null $service
     * @return bool
     */
    public function isServiceWorker(Service $service = null): bool
    {
        return $this->hasRole(Role::serviceWorker(), $service) || $this->isServiceAdmin($service);
    }

    /**
     * @param \App\Models\Service|null $service
     * @return bool
     */
    public function isServiceAdmin(Service $service = null): bool
    {
        return $this->hasRole(Role::serviceAdmin(), $service)
        || $this->isOrganisationAdmin($service->organisation ?? null);
    }

    /**
     * @param \App\Models\Organisation|null $organisation
     * @return bool
     */
    public function isOrganisationAdmin(Organisation $organisation = null): bool
    {
        return $this->hasRole(Role::organisationAdmin(), null, $organisation) || $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function isContentAdmin(): bool
    {
        return $this->hasRole(Role::contentAdmin()) || $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function isGlobalAdmin(): bool
    {
        return $this->hasRole(Role::globalAdmin()) || $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::superAdmin());
    }

    /**
     * @param \App\Models\Service $service
     * @return \App\Models\User
     */
    public function makeServiceWorker(Service $service): self
    {
        $this->assignRole(Role::serviceWorker(), $service);

        return $this;
    }

    /**
     * @param \App\Models\Service $service
     * @return \App\Models\User
     */
    public function makeServiceAdmin(Service $service): self
    {
        $this->makeServiceWorker($service);
        $this->assignRole(Role::serviceAdmin(), $service);

        return $this;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return \App\Models\User
     */
    public function makeOrganisationAdmin(Organisation $organisation): self
    {
        foreach ($organisation->services as $service) {
            $this->makeServiceWorker($service);
            $this->makeServiceAdmin($service);
        }

        $this->assignRole(Role::organisationAdmin(), null, $organisation);

        return $this;
    }

    /**
     * @return \App\Models\User
     */
    public function makeContentAdmin(): self
    {
        $this->assignRole(Role::contentAdmin());

        return $this;
    }

    /**
     * @return \App\Models\User
     */
    public function makeGlobalAdmin(): self
    {
        foreach (Organisation::all() as $organisation) {
            $this->makeOrganisationAdmin($organisation);
        }

        $this->assignRole(Role::globalAdmin());

        return $this;
    }

    /**
     * @return \App\Models\User
     */
    public function makeSuperAdmin(): self
    {
        foreach (Organisation::all() as $organisation) {
            $this->makeOrganisationAdmin($organisation);
        }
        $this->makeContentAdmin();
        $this->makeGlobalAdmin();
        $this->assignRole(Role::superAdmin());

        return $this;
    }

    /**
     * @param \App\Models\Service $service
     * @throws \App\Exceptions\CannotRevokeRoleException
     * @return \App\Models\User
     */
    public function revokeServiceWorker(Service $service)
    {
        if ($this->hasRole(Role::serviceAdmin(), $service)) {
            throw new CannotRevokeRoleException('Cannot revoke service worker role when user is a service admin');
        }

        return $this->removeRoll(Role::serviceWorker(), $service);
    }

    /**
     * @param \App\Models\Service $service
     * @throws \App\Exceptions\CannotRevokeRoleException
     * @return \App\Models\User
     */
    public function revokeServiceAdmin(Service $service)
    {
        if ($this->hasRole(Role::organisationAdmin(), null, $service->organisation)) {
            throw new CannotRevokeRoleException('Cannot revoke service admin role when user is an organisation admin');
        }

        $this->removeRoll(Role::serviceAdmin(), $service);

        return $this;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @throws \App\Exceptions\CannotRevokeRoleException
     * @return \App\Models\User
     */
    public function revokeOrganisationAdmin(Organisation $organisation)
    {
        if ($this->hasRole(Role::globalAdmin())) {
            throw new CannotRevokeRoleException('Cannot revoke organisation admin role when user is an global admin');
        }

        $this->removeRoll(Role::organisationAdmin(), null, $organisation);

        return $this;
    }

    /**
     * @throws \App\Exceptions\CannotRevokeRoleException
     * @return \App\Models\User
     */
    public function revokeContentAdmin()
    {
        if ($this->hasRole(Role::superAdmin())) {
            throw new CannotRevokeRoleException('Cannot revoke content admin role when user is an super admin');
        }

        $this->removeRoll(Role::contentAdmin());

        return $this;
    }

    /**
     * @throws \App\Exceptions\CannotRevokeRoleException
     * @return \App\Models\User
     */
    public function revokeGlobalAdmin()
    {
        if ($this->hasRole(Role::superAdmin())) {
            throw new CannotRevokeRoleException('Cannot revoke global admin role when user is an super admin');
        }

        $this->removeRoll(Role::globalAdmin());

        return $this;
    }

    /**
     * @return \App\Models\User
     */
    public function revokeSuperAdmin()
    {
        $this->removeRoll(Role::superAdmin());

        return $this;
    }

    /**
     * @param \App\Models\Service $service
     * @return bool
     */
    public function canMakeServiceWorker(Service $service): bool
    {
        return $this->isServiceWorker($service) && !($this->isGlobalAdmin() && !$this->isSuperAdmin());
    }

    /**
     * @param \App\Models\Service $service
     * @return bool
     */
    public function canMakeServiceAdmin(Service $service): bool
    {
        return $this->isServiceAdmin($service) && !($this->isGlobalAdmin() && !$this->isSuperAdmin());
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return bool
     */
    public function canMakeOrganisationAdmin(Organisation $organisation): bool
    {
        return $this->isOrganisationAdmin($organisation) && !($this->isGlobalAdmin() && !$this->isSuperAdmin());
    }

    /**
     * @return bool
     */
    public function canMakeContentAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function canMakeGlobalAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function canMakeSuperAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * @param \App\Models\User $subject
     * @param \App\Models\Service $service
     * @return bool
     */
    public function canRevokeServiceWorker(User $subject, Service $service): bool
    {
        return $this->canRevokeRole($subject, $service->organisation, $service);
    }

    /**
     * @param \App\Models\User $subject
     * @param \App\Models\Service $service
     * @return bool
     */
    public function canRevokeServiceAdmin(User $subject, Service $service): bool
    {
        return $this->canRevokeRole($subject, $service->organisation, $service);
    }

    /**
     * @param \App\Models\User $subject
     * @param \App\Models\Organisation $organisation
     * @return bool
     */
    public function canRevokeOrganisationAdmin(User $subject, Organisation $organisation): bool
    {
        return $this->canRevokeRole($subject, $organisation);
    }

    /**
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canRevokeContentAdmin(User $subject): bool
    {
        return $this->canRevokeRole($subject);
    }

    /**
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canRevokeGlobalAdmin(User $subject): bool
    {
        return $this->canRevokeRole($subject);
    }

    /**
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canRevokeSuperAdmin(User $subject): bool
    {
        return $this->canRevokeRole($subject);
    }

    /**
     * @param \App\Emails\Email $email
     * @return \App\Models\User
     */
    public function sendEmail(Email $email): self
    {
        Notification::sendEmail($email, $this);

        return $this;
    }

    /**
     * @param \App\Sms\Sms $sms
     * @return \App\Models\User
     */
    public function sendSms(Sms $sms): self
    {
        Notification::sendSms($sms, $this);

        return $this;
    }

    /**
     * @return \App\Models\User
     */
    public function clearSessions(): self
    {
        DB::table('sessions')
            ->where('user_id', $this->id)
            ->delete();

        return $this;
    }

    /**
     * @return \App\Models\Role|null
     */
    public function highestRole(): ?Role
    {
        return $this->orderedRoles()->first();
    }

    /**
     * Get the IDs of all the users that are in organisations and services
     * that this user belongs to who are at the same or lower permission level.
     *
     * @return \Illuminate\Support\Collection
     */
    public function visibleUserIds()
    {
        // Super admin can see all users
        if ($this->isSuperAdmin()) {
            return User::query()
                ->pluck('id')
                ->concat([$this->id])
                ->unique();
        }

        // Global Admin can only see self
        if ($this->isGlobalAdmin()) {
            return collect([$this->id]);
        }
        // Get the service IDs from all the organisations the user belongs to
        $serviceIds = Service::query()
            ->whereIn(table(Service::class, 'organisation_id'), $this->organisations()
                ->pluck(table(Organisation::class, 'id')))
            ->pluck(table(Service::class, 'id'));

        // Get all the users that belong to these services, except those that are super or global admins
        $userIds = $this->getUserIdsForServices($serviceIds, [
            Role::globalAdmin()->id,
            Role::superAdmin()->id,
        ]);

        // Get the service IDs from all the services the user belongs to
        $serviceIds = $this->services()
            ->wherePivot('role_id', '=', Role::serviceAdmin()->id)
            ->pluck(table(Service::class, 'id'));

        // Get all the users that belong to these services, except those that are super, global or organisation admins
        $userIds = $userIds->concat(
            $this->getUserIdsForServices($serviceIds, [
                Role::organisationAdmin()->id,
                Role::globalAdmin()->id,
                Role::superAdmin()->id,
            ])
        );

        // Include this user
        return $userIds
            ->concat([$this->id])
            ->unique();
    }

    /**
     * Get the ID's for the users.
     *
     * @param array $serviceIds
     * @param array $blacklistedRoleIds Exclude users who have these roles
     * @return \Illuminate\Support\Collection
     */
    protected function getUserIdsForServices(SupportCollection $serviceIds, array $blacklistedRoleIds): SupportCollection
    {
        return User::query()
            ->whereHas('userRoles', function (Builder $query) use ($serviceIds) {
                // Where the user has a permission for the service.
                $query->whereIn(table(UserRole::class, 'service_id'), $serviceIds);
            })
            ->whereDoesntHave('userRoles', function (Builder $query) use ($blacklistedRoleIds) {
                // Exclude users who have these roles.
                $query->whereIn(table(UserRole::class, 'role_id'), $blacklistedRoleIds);
            })
            ->pluck(table(User::class, 'id'));
    }
}
