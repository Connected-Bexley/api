<?php

declare (strict_types=1);

namespace App\Search\ElasticSearch;

use App\Contracts\EloquentMapper;
use App\Http\Resources\ServiceResource;
use App\Models\SearchHistory;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Support\Coordinate;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use ElasticScoutDriverPlus\Decorators\SearchResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ServiceEloquentMapper implements EloquentMapper
{
    public function paginate(SearchRequestBuilder $esQuery, int $page = null, int $perPage = null): AnonymousResourceCollection
    {
        $page = page($page);
        $perPage = per_page($perPage);

        $esQuery->load(['serviceLocations.location'], Service::class);

        $queryRequest = $esQuery->buildSearchRequest()->toArray();

        $response = $esQuery->execute();

        $this->logMetrics($queryRequest, $response);

        /**
         * Order the fetched service locations by distance.
         *
         * @todo Potential solution to the order nested locations in Elasticsearch: https://stackoverflow.com/a/43440405
         */
        $services = $this->orderServicesByLocation($queryRequest, $response->models());

        // If paginated, then create a new pagination instance.
        $services = new LengthAwarePaginator(
            $services,
            $response->total(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return ServiceResource::collection($services);
    }

    public function logMetrics(array $queryRequest, SearchResult $response): void
    {
        SearchHistory::create([
            'query' => $queryRequest,
            'count' => $response->total(),
        ]);
    }

    protected function orderServicesByLocation(array $queryRequest, Collection $services): Collection
    {
        $locations = array_filter($queryRequest['body']['sort'] ?? [], function ($key) {
            return $key === '_geo_distance';
        }, ARRAY_FILTER_USE_KEY);

        if (count($locations)) {
            return $services->each(function (Service $service) use ($locations) {
                $service->serviceLocations = $service->serviceLocations->sortBy(
                    function (ServiceLocation $serviceLocation) use ($locations) {
                        $location = $locations[0]['_geo_distance']['service_locations.location'];
                        $location = new Coordinate($location['lat'], $location['lon']);

                        return $location->distanceFrom($serviceLocation->location->toCoordinate());
                    }
                );
            });
        }

        return $services;
    }
}
