<?php

namespace App\Http\Controllers\Core\V1\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Event\Request;
use App\Search\ElasticSearch\EventEloquentMapper;
use App\Search\ElasticSearch\EventQueryBuilder;
use App\Search\SearchCriteriaQuery;
use App\Support\Coordinate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function __invoke(
        Request $request,
        SearchCriteriaQuery $criteria,
        EventQueryBuilder $builder,
        EventEloquentMapper $mapper
    ): AnonymousResourceCollection {
        // Apply query.
        if ($request->has('query')) {
            $criteria->setQuery($request->input('query'));
        }

        if ($request->has('category')) {
            // If category given then filter by category.
            $criteria->setCategories(explode(',', $request->input('category')));
        }

        // Apply filter on `is_free` field.
        if ($request->has('is_free')) {
            $criteria->setIsFree($request->input('is_free'));
        }

        // Apply filter on `is_virtual` field.
        if ($request->has('is_virtual')) {
            $criteria->setIsVirtual($request->input('is_virtual'));
        }

        // Apply filter on `has_wheelchair_access` field.
        if ($request->has('has_wheelchair_access')) {
            $criteria->setHasWheelchairAccess($request->input('has_wheelchair_access'));
        }

        // Apply filter on `has_induction_loop` field.
        if ($request->has('has_induction_loop')) {
            $criteria->setHasInductionLoop($request->input('has_induction_loop'));
        }

        // Apply filter on `has_accessible_toilet` field.
        if ($request->has('has_accessible_toilet')) {
            $criteria->setHasAccessibleToilet($request->input('has_accessible_toilet'));
        }

        // Apply filter on `starts_after` field.
        if ($request->has('starts_after')) {
            $criteria->setStartsAfter($request->input('starts_after'));
        }

        // Apply filter on `ends_before` field.
        if ($request->has('ends_before')) {
            $criteria->setEndsBefore($request->input('ends_before'));
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

        // Apply order.
        if ($request->has('order')) {
            $criteria->setOrder($request->input('order'));
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
