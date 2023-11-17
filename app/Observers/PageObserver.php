<?php

namespace App\Observers;

use App\Models\Page;

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
    }
}
