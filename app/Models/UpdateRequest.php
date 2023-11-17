<?php

namespace App\Models;

use App\Contracts\AppliesUpdateRequests;
use App\Models\Mutators\UpdateRequestMutators;
use App\Models\Relationships\UpdateRequestRelationships;
use App\Models\Scopes\UpdateRequestScopes;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class UpdateRequest extends Model
{
    use SoftDeletes;
    use UpdateRequestMutators;
    use UpdateRequestRelationships;
    use UpdateRequestScopes;

    const EXISTING_TYPE_LOCATION = 'locations';

    const EXISTING_TYPE_ORGANISATION = 'organisations';

    const EXISTING_TYPE_ORGANISATION_EVENT = 'organisation_events';

    const EXISTING_TYPE_PAGE = 'pages';

    const EXISTING_TYPE_REFERRAL = 'referrals';

    const EXISTING_TYPE_SERVICE = 'services';

    const EXISTING_TYPE_SERVICE_LOCATION = 'service_locations';

    const EXISTING_TYPE_USER = 'users';

    const NEW_TYPE_ORGANISATION_EVENT = 'new_organisation_event_created_by_org_admin';

    const NEW_TYPE_ORGANISATION_SIGN_UP_FORM = 'organisation_sign_up_form';

    const NEW_TYPE_ORGANISATION_GLOBAL_ADMIN = 'new_organisation_created_by_global_admin';

    const NEW_TYPE_PAGE = 'new_page';

    const NEW_TYPE_SERVICE_ORG_ADMIN = 'new_service_created_by_org_admin';

    const NEW_TYPE_SERVICE_GLOBAL_ADMIN = 'new_service_created_by_global_admin';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function isNew(): bool
    {
        return $this->updateable_id === null;
    }

    public function isExisting(): bool
    {
        return !$this->isNew();
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isDeclined(): bool
    {
        return $this->deleted_at !== null;
    }

    public function getValidationErrors(): MessageBag
    {
        return $this->getUpdateable()->validateUpdateRequest($this)->errors();
    }

    public function validate(): bool
    {
        return $this->getUpdateable()->validateUpdateRequest($this)->fails() === false;
    }

    public function apply(User $user = null): self
    {
        $this->getUpdateable()->applyUpdateRequest($this);
        $this->update([
            'actioning_user_id' => $user->id ?? null,
            'approved_at' => Date::now(),
        ]);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function delete(User $user = null): ?bool
    {
        if ($user) {
            $this->update(['actioning_user_id' => $user->id]);
        }

        return parent::delete();
    }

    public function getUpdateable(): AppliesUpdateRequests
    {
        return $this->isExisting()
        ? $this->updateable
        : $this->createUpdateableInstance();
    }

    protected function createUpdateableInstance(): AppliesUpdateRequests
    {
        $className = '\\App\\UpdateRequest\\' . Str::studly($this->updateable_type);

        return resolve($className);
    }
}
