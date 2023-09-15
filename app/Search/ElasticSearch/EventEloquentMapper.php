<?php

declare (strict_types=1);

namespace App\Search\ElasticSearch;

use App\Contracts\EloquentMapper;
use App\Http\Resources\OrganisationEventResource;
use App\Models\OrganisationEvent as Event;
use App\Models\SearchHistory;
use App\Support\Coordinate;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use ElasticScoutDriverPlus\Decorators\SearchResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class EventEloquentMapper implements EloquentMapper
{
    public function paginate(SearchRequestBuilder $esQuery, int $page = null, int $perPage = null): AnonymousResourceCollection
    {
        $page = page($page);
        $perPage = per_page($perPage);

        $queryRequest = $esQuery->buildSearchRequest()->toArray();

        $response = $esQuery->execute();

        $this->logMetrics($queryRequest, $response);

        /**
         * Order the fetched service locations by distance.
         * @todo Potential solution to the order nested locations in Elasticsearch: https://stackoverflow.com/a/43440405
         */
        $events = $this->orderEventsByLocation($queryRequest, $response->models());

        // If paginated, then create a new pagination instance.
        $events = new LengthAwarePaginator(
            $events,
            $response->total(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return OrganisationEventResource::collection($events);
    }

    public function logMetrics(array $queryRequest, SearchResult $response): void
    {
        SearchHistory::create([
            'query' => $queryRequest,
            'count' => $response->total(),
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $events
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function orderEventsByLocation(array $queryRequest, Collection $events): Collection
    {
        $locations = array_filter($queryRequest['body']['sort'] ?? [], function ($key) {
            return $key === '_geo_distance';
        }, ARRAY_FILTER_USE_KEY);

        if (count($locations)) {
            return $events->filter(function (Event $event) {
                return !$event->is_virtual;
            })
                ->sortBy(function (Event $event) use ($locations) {
                    $location = $locations[0]['_geo_distance']['event_location.location'];
                    $location = new Coordinate($location['lat'], $location['lon']);

                    return $location->distanceFrom($event->location->toCoordinate());
                });
        }

        return $events;
    }
}
