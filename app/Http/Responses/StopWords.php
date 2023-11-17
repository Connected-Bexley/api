<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class StopWords implements Responsable
{
    /**
     * @var array
     */
    protected $stopWords;

    /**
     * StopWords constructor.
     */
    public function __construct(array $stopWords)
    {
        $this->stopWords = $stopWords;
    }

    /**
     * Create an HTTP response that represents the object.
     * @param mixed $request
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json(['data' => $this->stopWords]);
    }
}
