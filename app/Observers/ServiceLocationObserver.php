<?php

namespace App\Observers;

use App\Models\ServiceLocation;

class ServiceLocationObserver
{
    /**
     * Handle the service location "created" event.
     */
    public function created(ServiceLocation $serviceLocation): void
    {
        $serviceLocation->touchService();
    }

    /**
     * Handle the service location "deleting" event.
     */
    public function deleting(ServiceLocation $serviceLocation)
    {
        $serviceLocation->updateRequests->each->delete();
        $serviceLocation->regularOpeningHours->each->delete();
        $serviceLocation->holidayOpeningHours->each->delete();
    }

    /**
     * Handle the service location "deleted" event.
     */
    public function deleted(ServiceLocation $serviceLocation): void
    {
        $serviceLocation->touchService();
    }
}
