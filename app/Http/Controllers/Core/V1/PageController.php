<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Page\DestroyRequest;
use App\Http\Requests\Page\IndexRequest;
use App\Http\Requests\Page\ShowRequest;
use App\Http\Requests\Page\StoreRequest;
use App\Http\Requests\Page\UpdateRequest;
use App\Http\Resources\PageResource;
use App\Http\Responses\ResourceDeleted;
use App\Http\Responses\UpdateRequestReceived;
use App\Models\Page;
use App\Models\UpdateRequest as UpdateRequestModel;
use App\Services\DataPersistence\PagePersistenceService;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class PageController extends Controller
{
    /**
     * PageController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Page\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $orderByCol = (new Page())->getLftName();
        $baseQuery = Page::query()
            ->with('parent')
            ->orderBy($orderByCol);

        if (!$request->user('api')) {
            $baseQuery->where('enabled', true);
        }

        $pages = QueryBuilder::for($baseQuery)
            ->allowedIncludes([
                'parent',
                'children',
                AllowedInclude::relationship('landing-page-ancestors', 'landingPageAncestors'),
            ])
            ->allowedFilters([
                AllowedFilter::scope('landing_page', 'pageDescendants'),
                AllowedFilter::exact('id'),
                AllowedFilter::exact('parent_id', 'parent_uuid'),
                AllowedFilter::exact('page_type'),
                'title',
            ])
            ->allowedSorts($orderByCol, 'title')
            ->defaultSort($orderByCol)
            ->get();

        event(EndpointHit::onRead($request, 'Viewed all information pages'));

        return PageResource::collection($pages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Page\StoreRequest $request
     * @param \App\Services\DataPersistence\PagePersistenceService $persistenceService
     * @return \App\Http\Resources\OrganisationResource
     */
    public function store(StoreRequest $request, PagePersistenceService $persistenceService)
    {
        $entity = $persistenceService->store($request);

        if ($entity instanceof UpdateRequestModel) {
            event(EndpointHit::onCreate($request, "Created page as update request [{$entity->id}]", $entity));

            return new UpdateRequestReceived($entity);
        }

        event(EndpointHit::onCreate($request, "Created page [{$entity->id}]", $entity));

        $entity->load('landingPageAncestors', 'parent', 'children', 'collectionCategories', 'collectionPersonas');

        return new PageResource($entity);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Page\ShowRequest $request
     * @param \App\Models\Page $page
     * @return \App\Http\Resources\OrganisationResource
     */
    public function show(ShowRequest $request, Page $page)
    {
        $baseQuery = Page::query()
            ->with(['landingPageAncestors', 'parent', 'children', 'collectionCategories', 'collectionPersonas'])
            ->where('id', $page->id);

        $page = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed page [{$page->id}]", $page));

        return new pageResource($page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Page\UpdateRequest $request
     * @param \App\Models\Page $page
     * @param \App\Services\DataPersistence\PagePersistenceService $persistenceService
     * @return \App\Http\Resources\OrganisationResource
     */
    public function update(UpdateRequest $request, Page $page, PagePersistenceService $persistenceService)
    {
        $updateRequest = $persistenceService->update($request, $page);

        event(EndpointHit::onUpdate($request, "Updated page [{$page->id}]", $page));

        return new UpdateRequestReceived($updateRequest);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Organisation\DestroyRequest $request
     * @param \App\Models\Page $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Page $page)
    {
        return DB::transaction(function () use ($request, $page) {
            event(EndpointHit::onDelete($request, "Deleted page [{$page->id}]", $page));

            $page->delete();

            return new ResourceDeleted('page');
        });
    }
}
