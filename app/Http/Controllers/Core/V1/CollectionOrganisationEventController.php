<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Generators\UniqueSlugGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionOrganisationEvent\DestroyRequest;
use App\Http\Requests\CollectionOrganisationEvent\IndexRequest;
use App\Http\Requests\CollectionOrganisationEvent\ShowRequest;
use App\Http\Requests\CollectionOrganisationEvent\StoreRequest;
use App\Http\Requests\CollectionOrganisationEvent\UpdateRequest;
use App\Http\Resources\CollectionOrganisationEventResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\Collection;
use App\Models\File;
use App\Models\Taxonomy;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CollectionOrganisationEventController extends Controller
{
    /**
     * CollectionOrganisationEventController constructor.
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
        $baseQuery = Collection::organisationEvents()
            ->orderBy('order');

        $organisationEventQuery = QueryBuilder::for($baseQuery)
            ->with('taxonomies');
        if ($request->is('*/all')) {
            $organisationEventCollections = $organisationEventQuery->get();
        } else {
            $organisationEventCollections = $organisationEventQuery->allowedFilters([
                AllowedFilter::exact('id'),
            ])
                ->paginate(per_page($request->per_page));
        }

        event(EndpointHit::onRead($request, 'Viewed all organisation event collections'));

        return CollectionOrganisationEventResource::collection($organisationEventCollections);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, UniqueSlugGenerator $slugGenerator)
    {
        return DB::transaction(function () use ($request, $slugGenerator) {
            // Parse the sideboxes.
            $sideboxes = array_map(function (array $sidebox): array {
                return [
                    'title' => $sidebox['title'],
                    'content' => sanitize_markdown($sidebox['content']),
                ];
            }, $request->sideboxes ?? []);

            // Create the collection record.
            $organisationEventCollection = Collection::create([
                'type' => Collection::TYPE_ORGANISATION_EVENT,
                'slug' => $slugGenerator->generate($request->name, table(Collection::class)),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'subtitle' => $request->subtitle,
                    'image_file_id' => $request->image_file_id,
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
                'enabled' => $request->enabled,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $organisationEventCollection->syncCollectionTaxonomies($taxonomies);

            // Reload the newly created pivot records.
            $organisationEventCollection->load('taxonomies');

            event(EndpointHit::onCreate($request, "Created organisation event collection [{$organisationEventCollection->id}]", $organisationEventCollection));

            return new CollectionOrganisationEventResource($organisationEventCollection);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowRequest $request, Collection $collection): CollectionOrganisationEventResource
    {
        $baseQuery = Collection::query()
            ->where('id', $collection->id);

        $collection = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed organisation event collection [{$collection->id}]", $collection));

        return new CollectionOrganisationEventResource($collection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, UniqueSlugGenerator $slugGenerator, Collection $collection)
    {
        return DB::transaction(function () use ($request, $slugGenerator, $collection) {
            // Parse the sideboxes.
            $sideboxes = array_map(function (array $sidebox): array {
                return [
                    'title' => $sidebox['title'],
                    'content' => sanitize_markdown($sidebox['content']),
                ];
            }, $request->sideboxes ?? []);

            // Update the collection record.
            $collection->update([
                'slug' => $slugGenerator->compareEquals($request->name, $collection->slug)
                    ? $collection->slug
                    : $slugGenerator->generate($request->name, table(Collection::class)),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'subtitle' => $request->subtitle,
                    'image_file_id' => $request->has('image_file_id')
                        ? $request->image_file_id
                        : $collection->meta['image_file_id'] ?? null,
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
                'enabled' => $request->enabled,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Update or create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $collection->syncCollectionTaxonomies($taxonomies);

            event(EndpointHit::onUpdate($request, "Updated organisation event collection [{$collection->id}]", $collection));

            return new CollectionOrganisationEventResource($collection);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Collection $collection)
    {
        return DB::transaction(function () use ($request, $collection) {
            event(EndpointHit::onDelete($request, "Deleted organisation event collection [{$collection->id}]", $collection));

            $collection->delete();

            return new ResourceDeleted('organisation event collection');
        });
    }
}
