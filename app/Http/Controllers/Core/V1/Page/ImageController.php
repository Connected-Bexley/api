<?php

namespace App\Http\Controllers\Core\V1\Page;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Page\Image\ShowRequest;
use App\Models\File;
use App\Models\Page;
use App\Models\UpdateRequest;

class ImageController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function show(ShowRequest $request, Page $page)
    {
        event(EndpointHit::onRead($request, "Viewed image for information page [{$page->id}]", $page));

        // Get the image file associated.
        $file = $page->image;

        // Use the file from an update request instead, if specified.
        if ($request->has('update_request_id')) {
            $imageId = UpdateRequest::query()
                ->pageId($page->id)
                ->where('id', '=', $request->update_request_id)
                ->firstOrFail()
                ->data['image_file_id'];

            /** @var File $file */
            $file = File::findOrFail($imageId);
        }

        // Return the file or null.
        return $file ? $file->resizedVersion($request->max_dimension) : null;
    }

    /**
     * Display the specified resource.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function showNew(ShowRequest $request)
    {
        if ($request->has('update_request_id')) {
            // Use the file from an update request instead, if specified.
            $imageId = UpdateRequest::query()
                ->where('id', '=', $request->update_request_id)
                ->firstOrFail()
                ->data['image_file_id'];

            /** @var File $file */
            $file = File::findOrFail($imageId);
        }

        // Return the file or null.
        return $file->resizedVersion($request->max_dimension);
    }
}
