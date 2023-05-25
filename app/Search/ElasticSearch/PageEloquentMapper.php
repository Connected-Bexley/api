<?php

declare(strict_types=1);

namespace App\Search\ElasticSearch;

use App\Contracts\EloquentMapper;
use App\Http\Resources\PageResource;
use App\Models\SearchHistory;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use ElasticScoutDriverPlus\Decorators\SearchResult;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PageEloquentMapper implements EloquentMapper
{
    public function paginate(SearchRequestBuilder $esQuery, int $page = null, int $perPage = null): AnonymousResourceCollection
    {
        $page = page($page);
        $perPage = per_page($perPage);

        $queryRequest = $esQuery->buildSearchRequest()->toArray();
        $response = $esQuery->execute();

        $this->logMetrics($queryRequest, $response);

        // If paginated, then create a new pagination instance.
        $pages = new LengthAwarePaginator(
            $response->models(),
            $response->total(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return PageResource::collection($pages);
    }

    public function logMetrics(array $queryRequest, SearchResult $response): void
    {
        SearchHistory::create([
            'query' => $queryRequest,
            'count' => $response->total(),
        ]);
    }
}
