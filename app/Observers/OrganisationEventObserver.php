<?php

namespace App\Observers;

use App\Models\OrganisationEvent;
use App\Models\UpdateRequest;

class OrganisationEventObserver
{
    /**
     * Handle the organisation event "deleting" event.
     */
    public function deleting(OrganisationEvent $event)
    {
        if ($event->updateRequests->isNotEmpty()) {
            $event->updateRequests->each(function (UpdateRequest $updateRequest) {
                $updateRequest->delete();
            });
        }
    }
}
