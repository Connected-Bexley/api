<?php

namespace App\Http\Controllers\Core\V1\OrganisationEvent;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganisationEvent\Image\ShowRequest;
use App\Models\File;
use App\Models\OrganisationEvent;
use App\Models\UpdateRequest;

class ImageController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\OrganisationEvent\Image\ShowRequest $request
     * @param \App\Models\OrganisationEvent $organisationEvent
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function show(ShowRequest $request, OrganisationEvent $organisationEvent)
    {
        event(EndpointHit::onRead($request, "Viewed image for Organisation Event [{$organisationEvent->id}]", $organisationEvent));

        // Get the image file associated.
        $file = File::find($organisationEvent->image_file_id);

        // Use the file from an update request instead, if specified.
        if ($request->has('update_request_id')) {
            $imageFileId = UpdateRequest::query()
                ->organisationEventId($organisationEvent->id)
                ->where('id', '=', $request->update_request_id)
                ->firstOrFail()
                ->data['image_file_id'];

            /** @var \App\Models\File $file */
            $file = File::findOrFail($imageFileId);
        }

        // Return the file, or placeholder if the file is null.
        return $file?->resizedVersion($request->max_dimension) ?? OrganisationEvent::placeholderImage($request->max_dimension);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\OrganisationEvent\Image\ShowRequest $request
     * @param string $organisationEventId
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function showNew(ShowRequest $request)
    {
        if ($request->has('update_request_id')) {
            // Use the file from an update request if specified.

            $imageFileId = UpdateRequest::query()
                ->where('id', '=', $request->update_request_id)
                ->firstOrFail()
                ->data['image_file_id'];

            /** @var \App\Models\File $file */
            $file = File::findOrFail($imageFileId);
        }

        // Return the file, or placeholder if the file is null.
        return $file?->resizedVersion($request->max_dimension) ?? OrganisationEvent::placeholderImage($request->max_dimension);
    }
}
