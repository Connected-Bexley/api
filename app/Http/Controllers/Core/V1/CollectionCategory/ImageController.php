<?php

namespace App\Http\Controllers\Core\V1\CollectionCategory;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionCategory\Image\ShowRequest;
use App\Models\Collection;
use App\Models\File;

class ImageController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ShowRequest $request, Collection $collection)
    {
        event(EndpointHit::onRead($request, "Viewed image for collection Category [{$collection->id}]", $collection));

        // Get the logo file associated.
        $file = File::find($collection->meta['image_file_id'] ?? null);

        // Return the file, or placeholder if the file is null.
        return $file?->resizedVersion($request->max_dimension) ?? Collection::categoryPlaceholderLogo($request->max_dimension);
    }
}
