<?php

namespace App\Http\Controllers\Core\V1\Service;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Logo\ShowRequest;
use App\Models\File;
use App\Models\Service;
use App\Models\UpdateRequest;

class LogoController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Service\Logo\ShowRequest $request
     * @param \App\Models\Service $service
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function show(ShowRequest $request, Service $service)
    {
        event(EndpointHit::onRead($request, "Viewed logo for service [{$service->id}]", $service));

        // Get the logo file associated.
        $file = $service->logoFile;

        // Use the file from an update request instead, if specified.
        if ($request->has('update_request_id')) {
            $logoFileId = UpdateRequest::query()
                ->serviceId($service->id)
                ->where('id', '=', $request->update_request_id)
                ->firstOrFail()
                ->data['logo_file_id'];

            /** @var \App\Models\File $file */
            $file = File::findOrFail($logoFileId);
        }

        // Return the file, or placeholder if the file is null.
        return $file?->resizedVersion($request->max_dimension)
            ?? Service::placeholderLogo($request->max_dimension);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Service\Logo\ShowRequest $request
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function showNew(ShowRequest $request)
    {
        if ($request->has('update_request_id')) {
            // Use the file from an update request instead, if specified.
            $logoFileId = UpdateRequest::query()
                ->where('id', '=', $request->update_request_id)
                ->firstOrFail()
                ->data['logo_file_id'];

            /** @var \App\Models\File $file */
            $file = File::findOrFail($logoFileId);
        }

        // Return the file, or placeholder if the file is null.
        return $file?->resizedVersion($request->max_dimension)
            ?? Service::placeholderLogo($request->max_dimension);
    }
}
