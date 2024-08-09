<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\File\ShowRequest;
use App\Http\Requests\File\StoreRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    /**
     * FileController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('show', 'display');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            /** @var File $file */
            $file = File::create([
                'filename' => uuid() . File::extensionFromMime($request->mime_type),
                'mime_type' => $request->mime_type,
                'meta' => [
                    'type' => File::META_TYPE_PENDING_ASSIGNMENT,
                    'alt_text' => $request->alt_text,
                ],
                'is_private' => $request->is_private,
            ]);

            $file->uploadBase64EncodedFile($request->file);

            event(EndpointHit::onCreate($request, "Created file [{$file->id}]", $file));

            return new FileResource($file);
        });
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ShowRequest $request, File $file)
    {
        return new FileResource($file);
    }

    /**
     * Download streamed binary of an image file.
     *
     * @return \Illuminate\Http\Response
     */
    public function display(ShowRequest $request, string $fileName)
    {
        $file = File::where('filename', $fileName)->firstOrFail();

        return response()->streamDownload(function () use ($request, $file) {
            echo $file->resizedVersion($request->query('max_dimension', null))->getContent();
        }, $file->filename, [
            'Access-Control-Expose-Headers' => ['Content-Type', 'Content-Disposition'],
            'Content-Type' => $file->mime_type,
        ], 'inline');
        // return response()->file($file->url(), [
        //     'Access-Control-Expose-Headers' => ['Content-Type', 'Content-Disposition'],
        //     'Content-Type' => $file->mime_type,
        //     'Content-Disposition' => sprintf('inline; filename="%s"', $file->filename),
        // ]);
        // return response()->streamDownload(function () use ($file) {
        //     return $file->toResponse();
        // }, $file->id . File::extensionFromMime($file->mime_type));
    }
}
