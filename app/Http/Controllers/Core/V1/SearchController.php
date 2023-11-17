<?php

namespace App\Http\Controllers\Core\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Request;
use App\Search\ElasticSearch\ServiceEloquentMapper;
use App\Search\ElasticSearch\ServiceQueryBuilder;
use App\Search\SearchCriteriaQuery;
use App\Support\Coordinate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function __invoke(
        Request $request,
        SearchCriteriaQuery $criteria,
        ServiceQueryBuilder $builder,
        ServiceEloquentMapper $mapper
    ): AnonymousResourceCollection {
        // Apply query.
        if ($request->has('query')) {
            $criteria->setQuery($request->input('query'));
        }

        if ($request->has('category')) {
            // If category given then filter by category.
            $criteria->setCategories(explode(',', $request->input('category')));
        }

        if ($request->has('persona')) {
            // Otherwise, if persona given then filter by persona.
            $criteria->setPersonas(explode(',', $request->input('persona')));
        }

        // Apply filter on `wait_time` field.
        if ($request->has('wait_time')) {
            $criteria->setWaitTime($request->input('wait_time'));
        }

        // Apply filter on `is_free` field.
        if ($request->has('is_free')) {
            $criteria->setIsFree($request->input('is_free'));
        }

        // If location was passed, then parse the location.
        if ($request->has('location')) {
            $criteria->setLocation(
                new Coordinate(
                    $request->input('location.lat'),
                    $request->input('location.lon')
                )
            );

            if ($request->has('distance')) {
                $criteria->setDistance($request->input('distance'));
            }
        }

        if ($request->has('eligibilities')) {
            $criteria->setEligibilities($request->input('eligibilities'));
        }

        // Apply order.
        if ($request->has('order')) {
            $criteria->setOrder($request->input('order'));
        }

        // Get the pagination values
        $page = page($request->input('page'));
        $perPage = per_page($request->input('per_page'));

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
