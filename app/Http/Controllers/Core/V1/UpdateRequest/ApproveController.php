<?php

namespace App\Http\Controllers\Core\V1\UpdateRequest;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRequest\Approve\UpdateRequest as Request;
use App\Http\Resources\UpdateRequestResource;
use App\Models\Page;
use App\Models\Service;
use App\Models\UpdateRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApproveController extends Controller
{
    /**
     * ApproveController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws \Exception
     * @return Response
     */
    public function update(Request $request, UpdateRequest $updateRequest)
    {
        if (!$updateRequest->validate()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $updateRequest->getValidationErrors()->toArray(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($request, $updateRequest) {
            $approvedUpdateRequest = $updateRequest->apply($request->user('api'));

            event(EndpointHit::onUpdate($request, "Approved update request [{$updateRequest->id}]", $updateRequest));

            if ($request->query('action') === 'edit') {
                if (in_array($approvedUpdateRequest->updateable_type, [UpdateRequest::NEW_TYPE_SERVICE_GLOBAL_ADMIN, UpdateRequest::NEW_TYPE_SERVICE_ORG_ADMIN])) {
                    $service = Service::find($approvedUpdateRequest->updateable_id);
                    $service->update(['status' => Service::STATUS_INACTIVE]);
                } elseif ($approvedUpdateRequest->updateable_type === UpdateRequest::NEW_TYPE_PAGE) {
                    $page = Page::find($approvedUpdateRequest->updateable_id);
                    $page->update(['enabled' => Page::DISABLED]);
                }
            }

            return new UpdateRequestResource($approvedUpdateRequest);
        });
    }
}
