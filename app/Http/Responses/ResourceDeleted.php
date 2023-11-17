<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ResourceDeleted implements Responsable
{
    /**
     * @var string
     */
    protected $resource;

    /**
     * ResourceDeleted constructor.
     */
    public function __construct(string $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create an HTTP response that represents the object.
     * @param mixed $request
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json(['message' => "The {$this->resource} has been successfully deleted"]);
    }
}
