<?php

namespace App\Observers;

use App\Models\Location;

class LocationObserver
{
    /**
     * Handle the location "updated" event.
     */
    public function updated(Location $location): void
    {
        $location->touchServices();
    }

    /**
     * Handle the organisation "deleting" event.
     */
    public function deleting(Location $location)
    {
        $location->updateRequests->each->delete();
        $location->serviceLocations->each->delete();
    }

    /**
     * Handle the organisation "deleted" event.
     */
    public function deleted(Location $location): void
    {
        $location->touchServices();
    }
}
