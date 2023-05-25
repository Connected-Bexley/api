<?php

namespace App\Contracts;

use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface EloquentMapper
{
    /**
     * Performs the search on the model and returns a paginated collection response.
     *
     * @param \ElasticScoutDriverPlus\Builders\SearchRequestBuilder $esQuery
     * @param int $page
     * @param int $perPage
     * @return AnonymousResourceCollection
     */
    public function paginate(SearchRequestBuilder $esQuery, int $page, int $perPage): AnonymousResourceCollection;
}
