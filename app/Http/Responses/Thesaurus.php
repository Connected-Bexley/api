<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class Thesaurus implements Responsable
{
    /**
     * @var array
     */
    protected $thesaurus;

    /**
     * Thesaurus constructor.
     */
    public function __construct(array $thesaurus)
    {
        $this->thesaurus = $thesaurus;
    }

    /**
     * Create an HTTP response that represents the object.
     * @param mixed $request
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json(['data' => $this->thesaurus]);
    }
}
