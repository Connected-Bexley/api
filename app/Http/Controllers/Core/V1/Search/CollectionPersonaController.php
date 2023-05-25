<?php

namespace App\Http\Controllers\Core\V1\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Collection\PersonaRequest;
use App\Search\ElasticSearch\CollectionPersonaQueryBuilder;
use App\Search\ElasticSearch\ServiceEloquentMapper;
use App\Search\SearchCriteriaQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CollectionPersonaController extends Controller
{
    /**
     * @param \App\Http\Requests\Search\Collection\PersonaRequest $request
     * @param \App\Search\SearchCriteriaQuery $criteria
     * @param \App\Search\ElasticSearch\CollectionPersonaQueryBuilder $builder
     * @param \App\Search\ElasticSearch\ServiceEloquentMapper $mapper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(
        PersonaRequest $request,
        SearchCriteriaQuery $criteria,
        CollectionPersonaQueryBuilder $builder,
        ServiceEloquentMapper $mapper
    ): AnonymousResourceCollection {
        $criteria->setPersonas([$request->input('persona')]);

        // Get the pagination values
        $page = page($request->input('page'));
        $perPage = per_page($request->input('per_page'));

        // Create the query
        $esQuery = $builder->build(
            $criteria,
            $page,
            $perPage
        );

        return $mapper->paginate(
            $esQuery,
            $page,
            $perPage
        );
    }
}
