<?php

namespace App\Http\Responses;

use App\Models\UpdateRequest;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UpdateRequestReceived implements Responsable
{
    /**
     * @var UpdateRequest
     */
    protected $updateRequest;

    /**
     * @var int
     */
    protected $code;

    /**
     * UpdateRequestReceived constructor.
     */
    public function __construct(UpdateRequest $updateRequest, int $code = Response::HTTP_OK)
    {
        $this->updateRequest = $updateRequest;
        $this->code = $code;
    }

    /**
     * Create an HTTP response that represents the object.
     * @param mixed $request
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'message' => $this->updateRequest->isApproved() ? __('updates.pre-approved') : __('updates.pending', ['appname' => config('app.name', 'Laravel')]),
            'id' => $this->updateRequest->id,
            'data' => $this->updateRequest->getUpdateable()->getData(
                $this->updateRequest->data
            ),
        ], $this->code);
    }
}
