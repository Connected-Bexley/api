<?php

declare (strict_types=1);

namespace App\Http\Controllers\Core\V1\Search;

use App\Http\Requests\Search\Page\Request;
use App\Search\ElasticSearch\PageEloquentMapper;
use App\Search\ElasticSearch\PageQueryBuilder;
use App\Search\SearchCriteriaQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController
{
    /**
     * @param \App\Http\Requests\Search\Page\Request $request
     * @param \App\Search\SearchCriteriaQuery $criteria
     * @param \App\Search\ElasticSearch\PageQueryBuilder $builder
     * @param \App\Search\ElasticSearch\PageEloquentMapper $mapper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(
        Request $request,
        SearchCriteriaQuery $criteria,
        PageQueryBuilder $builder,
        PageEloquentMapper $mapper
    ): AnonymousResourceCollection {
        // Apply query.
        if ($request->has('query')) {
            $criteria->setQuery($request->input('query'));
        }

        // Get the pagination values
        $page = page((int)$request->input('page'));
        $perPage = per_page((int)$request->input('per_page'));

        // Create the query
        $esQuery = $builder->build(
            $criteria,
            $page,
            $perPage
        );

        // Perform the search.
        return $mapper->paginate(
            $esQuery,
            $page,
            $perPage
        );
    }
}
