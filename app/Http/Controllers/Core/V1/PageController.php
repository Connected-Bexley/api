<?php

namespace App\Http\Controllers\Core\V1;

use App\Models\Page;
use App\Events\EndpointHit;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\Page\ShowRequest;
use App\Http\Responses\ResourceDeleted;
use Spatie\QueryBuilder\AllowedInclude;
use App\Http\Requests\Page\IndexRequest;
use App\Http\Requests\Page\StoreRequest;
use App\Http\Requests\Page\UpdateRequest;
use App\Http\Requests\Page\DestroyRequest;
use App\Http\Responses\UpdateRequestReceived;
use App\Models\UpdateRequest as UpdateRequestModel;
use App\Services\DataPersistence\PagePersistenceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
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
                AllowedInclude::relationship('landingPageAncestors', 'landingPageAncestors'),
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
     */
    public function show(ShowRequest $request, Page $page): PageResource
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
     */
    public function update(UpdateRequest $request, Page $page, PagePersistenceService $persistenceService): UpdateRequestReceived
    {
        $updateRequest = $persistenceService->update($request, $page);

        event(EndpointHit::onUpdate($request, "Updated page [{$page->id}]", $page));

        return new UpdateRequestReceived($updateRequest);
    }

    /**
     * Remove the specified resource from storage.
     *
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
