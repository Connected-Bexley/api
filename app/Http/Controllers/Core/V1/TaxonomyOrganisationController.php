<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Generators\UniqueSlugGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaxonomyOrganisation\DestroyRequest;
use App\Http\Requests\TaxonomyOrganisation\IndexRequest;
use App\Http\Requests\TaxonomyOrganisation\ShowRequest;
use App\Http\Requests\TaxonomyOrganisation\StoreRequest;
use App\Http\Requests\TaxonomyOrganisation\UpdateRequest;
use App\Http\Resources\TaxonomyOrganisationResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\Taxonomy;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class TaxonomyOrganisationController extends Controller
{
    /**
     * TaxonomyOrganisationController constructor.
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
        $baseQuery = Taxonomy::query()
            ->organisations()
            ->orderBy('order');

        $organisations = QueryBuilder::for($baseQuery)
            ->get();

        event(EndpointHit::onRead($request, 'Viewed all taxonomy organisations'));

        return TaxonomyOrganisationResource::collection($organisations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, UniqueSlugGenerator $slugGenerator)
    {
        return DB::transaction(function () use ($request, $slugGenerator) {
            $organisation = Taxonomy::organisation()->children()->create([
                'slug' => $slugGenerator->generate($request->name, (new Taxonomy())),
                'name' => $request->name,
                'order' => $request->order,
                'depth' => 1,
            ]);

            event(EndpointHit::onCreate($request, "Created taxonomy organisation [{$organisation->id}]", $organisation));

            return new TaxonomyOrganisationResource($organisation);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowRequest $request, Taxonomy $taxonomy): TaxonomyOrganisationResource
    {
        $baseQuery = Taxonomy::query()
            ->where('id', $taxonomy->id);

        $taxonomy = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed taxonomy organisation [{$taxonomy->id}]", $taxonomy));

        return new TaxonomyOrganisationResource($taxonomy);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, UniqueSlugGenerator $slugGenerator, Taxonomy $taxonomy)
    {
        return DB::transaction(function () use ($request, $slugGenerator, $taxonomy) {
            $taxonomy->update([
                'slug' => $slugGenerator->generate($request->name, $taxonomy),
                'name' => $request->name,
                'order' => $request->order,
            ]);

            event(EndpointHit::onUpdate($request, "Updated taxonomy organisation [{$taxonomy->id}]", $taxonomy));

            return new TaxonomyOrganisationResource($taxonomy);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Taxonomy $taxonomy)
    {
        return DB::transaction(function () use ($request, $taxonomy) {
            event(EndpointHit::onDelete($request, "Deleted taxonomy organisation [{$taxonomy->id}]", $taxonomy));

            $taxonomy->delete();

            return new ResourceDeleted('taxonomy organisation');
        });
    }
}
