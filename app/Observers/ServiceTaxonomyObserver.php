<?php

namespace App\Observers;

use App\Models\ServiceTaxonomy;

class ServiceTaxonomyObserver
{
    /**
     * Handle to the service taxonomy "created" event.
     */
    public function created(ServiceTaxonomy $serviceTaxonomy): void
    {
        $serviceTaxonomy->touchService();
    }

    /**
     * Handle the service taxonomy "deleted" event.
     */
    public function deleted(ServiceTaxonomy $serviceTaxonomy): void
    {
        $serviceTaxonomy->touchService();
    }
}
