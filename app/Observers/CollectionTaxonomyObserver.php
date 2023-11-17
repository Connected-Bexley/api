<?php

namespace App\Observers;

use App\Models\CollectionTaxonomy;

class CollectionTaxonomyObserver
{
    /**
     * Handle to the collection taxonomy "created" event.
     */
    public function created(CollectionTaxonomy $collectionTaxonomy): void
    {
        $collectionTaxonomy->touchServices();
    }

    /**
     * Handle the collection taxonomy "deleted" event.
     */
    public function deleted(CollectionTaxonomy $collectionTaxonomy): void
    {
        $collectionTaxonomy->touchServices();
    }
}
