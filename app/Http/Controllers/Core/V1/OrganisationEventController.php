<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\OrganisationEvent\HasPermissionFilter;
use App\Http\Requests\OrganisationEvent\DestroyRequest;
use App\Http\Requests\OrganisationEvent\IndexRequest;
use App\Http\Requests\OrganisationEvent\ShowRequest;
use App\Http\Requests\OrganisationEvent\StoreRequest;
use App\Http\Requests\OrganisationEvent\UpdateRequest;
use App\Http\Resources\OrganisationEventResource;
use App\Http\Responses\ResourceDeleted;
use App\Http\Responses\UpdateRequestReceived;
use App\Models\OrganisationEvent;
use App\Models\UpdateRequest as UpdateRequestModel;
use App\Services\DataPersistence\OrganisationEventPersistenceService;
use DateTime;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrganisationEventController extends Controller
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
        $baseQuery = OrganisationEvent::query();

        if (!$request->user() && !$request->has('filter[ends_after]')) {
            $baseQuery->endsAfter((new DateTime('now'))->format('Y-m-d'));
        }

        $organisationEvents = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('organisation_id'),
                AllowedFilter::exact('homepage'),
                'title',
                AllowedFilter::scope('starts_before'),
                AllowedFilter::scope('starts_after'),
                AllowedFilter::scope('ends_before'),
                AllowedFilter::scope('ends_after'),
                AllowedFilter::scope('has_wheelchair_access', 'hasWheelchairAccess'),
                AllowedFilter::scope('has_induction_loop', 'hasInductionLoop'),
                AllowedFilter::scope('collections', 'inCollections'),
                AllowedFilter::custom('has_permission', new HasPermissionFilter()),
            ])
            ->allowedIncludes(['organisation'])
            ->allowedSorts([
                'start_date',
                '-start_date',
                'end_date',
                '-end_date',
                'title',
            ])
            ->defaultSort('start_date')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all organisation events'));

        return OrganisationEventResource::collection($organisationEvents);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, OrganisationEventPersistenceService $persistenceService)
    {
        $entity = $persistenceService->store($request);

        if ($entity instanceof UpdateRequestModel) {
            event(EndpointHit::onCreate($request, "Created organisation event as update request [{$entity->id}]", $entity));

            return new UpdateRequestReceived($entity);
        }

        // Ensure conditional fields are reset if needed.
        $entity->resetConditionalFields();

        event(EndpointHit::onCreate($request, "Created organisation event [{$entity->id}]", $entity));

        return new OrganisationEventResource($entity);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowRequest $request, OrganisationEvent $organisationEvent): OrganisationEventResource
    {
        $baseQuery = OrganisationEvent::query()
            ->where('id', $organisationEvent->id);

        $organisationEvent = QueryBuilder::for($baseQuery)
            ->allowedIncludes(['organisation', 'location'])
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed Organisation Event [{$organisationEvent->id}]", $organisationEvent));

        return new OrganisationEventResource($organisationEvent);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateRequest $request,
        OrganisationEvent $organisationEvent,
        OrganisationEventPersistenceService $persistenceService
    ): UpdateRequestReceived {
        $updateRequest = $persistenceService->update($request, $organisationEvent);

        event(EndpointHit::onUpdate($request, "Updated organisation event [{$organisationEvent->id}]", $organisationEvent));

        return new UpdateRequestReceived($updateRequest);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, OrganisationEvent $organisationEvent)
    {
        return DB::transaction(function () use ($request, $organisationEvent) {
            event(EndpointHit::onDelete($request, "Deleted service [{$organisationEvent->id}]", $organisationEvent));

            $organisationEvent->delete();

            return new ResourceDeleted('service');
        });
    }
}
