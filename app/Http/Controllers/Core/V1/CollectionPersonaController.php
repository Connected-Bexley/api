<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Generators\UniqueSlugGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionPersona\DestroyRequest;
use App\Http\Requests\CollectionPersona\IndexRequest;
use App\Http\Requests\CollectionPersona\ShowRequest;
use App\Http\Requests\CollectionPersona\StoreRequest;
use App\Http\Requests\CollectionPersona\UpdateRequest;
use App\Http\Resources\CollectionPersonaResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\Collection;
use App\Models\File;
use App\Models\Taxonomy;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CollectionPersonaController extends Controller
{
    /**
     * CollectionPersonaController constructor.
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
        $baseQuery = Collection::personas()
            ->orderBy('order');

        $personaQuery = QueryBuilder::for($baseQuery)
            ->with('taxonomies');
        if ($request->is('*/all')) {
            $personas = $personaQuery->get();
        } else {
            $personas = $personaQuery->allowedFilters([
                AllowedFilter::exact('id'),
            ])
                ->paginate(per_page($request->per_page));
        }

        event(EndpointHit::onRead($request, 'Viewed all collection personas'));

        return CollectionPersonaResource::collection($personas);
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
            $persona = Collection::create([
                'type' => Collection::TYPE_PERSONA,
                'slug' => $slugGenerator->generate($request->name, (new Collection())),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'subtitle' => $request->subtitle,
                    'image_file_id' => $request->image_file_id,
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
                'enabled' => $request->enabled,
                'homepage' => $request->homepage,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $persona->syncCollectionTaxonomies($taxonomies);

            // Reload the newly created pivot records.
            $persona->load('taxonomies');

            event(EndpointHit::onCreate($request, "Created collection persona [{$persona->id}]", $persona));

            return new CollectionPersonaResource($persona);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowRequest $request, Collection $collection): CollectionPersonaResource
    {
        $baseQuery = Collection::query()
            ->where('id', $collection->id);

        $collection = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed collection persona [{$collection->id}]", $collection));

        return new CollectionPersonaResource($collection);
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
                'slug' => $slugGenerator->generate($request->name, $collection),
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
                'homepage' => $request->homepage,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Update or create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $collection->syncCollectionTaxonomies($taxonomies);

            event(EndpointHit::onUpdate($request, "Updated collection persona [{$collection->id}]", $collection));

            return new CollectionPersonaResource($collection);
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
            event(EndpointHit::onDelete($request, "Deleted collection persona [{$collection->id}]", $collection));

            $collection->delete();

            return new ResourceDeleted('collection persona');
        });
    }
}
