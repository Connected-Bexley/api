<?php

namespace App\Observers;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;

class OrganisationObserver
{
    /**
     * Handle the organisation "created" event.
     */
    public function created(Organisation $organisation): void
    {
        Role::globalAdmin()
            ->users()
            ->get()
            ->concat(Role::superAdmin()->users()->get())
            ->unique('id')
            ->each(function (User $user) use ($organisation) {
                $user->makeOrganisationAdmin($organisation);
            });
    }

    /**
     * Handle the organisation "updated" event.
     */
    public function updated(Organisation $organisation): void
    {
        $organisation->touchServices();
    }

    /**
     * Handle the organisation "deleting" event.
     */
    public function deleting(Organisation $organisation)
    {
        $organisation->userRoles->each->delete();
        $organisation->updateRequests->each->delete();
        $organisation->services->each->delete();
    }
}
