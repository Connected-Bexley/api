<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRolesUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var Collection
     */
    public $oldRoles;

    /**
     * @var Collection
     */
    public $newRoles;

    /**
     * UserPermissionsUpdated constructor.
     */
    public function __construct(User $user, Collection $oldRoles, Collection $newRoles)
    {
        $this->user = $user;
        $this->oldRoles = $oldRoles;
        $this->newRoles = $newRoles;
    }
}
