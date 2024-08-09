<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\UpdateRequest;

class PageObserver
{
    /**
     * Handle the information page "deleting" event.
     */
    public function deleting(Page $page)
    {
        if ($page->image_file_id) {
            $image = $page->image;
            $page->update([
                'image_file_id' => null,
            ]);
            $image->delete();
        }

        if ($page->updateRequests->isNotEmpty()) {
            $page->updateRequests->each(function (UpdateRequest $updateRequest) {
                $updateRequest->delete();
            });
        }
    }
}
