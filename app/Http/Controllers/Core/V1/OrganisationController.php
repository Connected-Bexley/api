<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\Organisation\HasPermissionFilter;
use App\Http\Requests\Organisation\DestroyRequest;
use App\Http\Requests\Organisation\IndexRequest;
use App\Http\Requests\Organisation\ShowRequest;
use App\Http\Requests\Organisation\StoreRequest;
use App\Http\Requests\Organisation\UpdateRequest;
use App\Http\Resources\OrganisationResource;
use App\Http\Responses\ResourceDeleted;
use App\Http\Responses\UpdateRequestReceived;
use App\Models\Organisation;
use App\Models\UpdateRequest as UpdateRequestModel;
use App\Services\DataPersistence\OrganisationPersistenceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrganisationController extends Controller
{
    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $baseQuery = Organisation::query();

        $organisations = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                AllowedFilter::custom('has_permission', new HasPermissionFilter()),
            ])
            ->allowedSorts('name')
            ->defaultSort('name')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all organisations'));

        return OrganisationResource::collection($organisations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, OrganisationPersistenceService $persistenceService)
    {
        $entity = $persistenceService->store($request);

        if ($entity instanceof UpdateRequestModel) {
            event(EndpointHit::onCreate($request, "Created organisation as update request [{$entity->id}]", $entity));

            return new UpdateRequestReceived($entity);
        }

        event(EndpointHit::onCreate($request, "Created organisation [{$entity->id}]", $entity));

        return new OrganisationResource($entity);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowRequest $request, Organisation $organisation): OrganisationResource
    {
        $baseQuery = Organisation::query()
            ->where('id', $organisation->id);

        $organisation = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed organisation [{$organisation->id}]", $organisation));

        return new OrganisationResource($organisation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \app\Services\DataPersistence\OrganisationPersistenceService
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Organisation $organisation, OrganisationPersistenceService $persistenceService)
    {
        $updateRequest = $persistenceService->update($request, $organisation);

        event(EndpointHit::onUpdate($request, "Updated organisation [{$organisation->id}]", $organisation));

        return new UpdateRequestReceived($updateRequest);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Organisation $organisation)
    {
        return DB::transaction(function () use ($request, $organisation) {
            event(EndpointHit::onDelete($request, "Deleted organisation [{$organisation->id}]", $organisation));

            $organisation->delete();

            return new ResourceDeleted('organisation');
        });
    }
}
