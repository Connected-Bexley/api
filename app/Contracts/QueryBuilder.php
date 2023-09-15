<?php

namespace App\Contracts;

use App\Search\SearchCriteriaQuery;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;

interface QueryBuilder
{
    /**
     * Build the search query.
     *
     * @param \App\Search\SearchCriteriaQuery $query
     * @param int $page
     * @param int $perPage
     * @return ElasticScoutDriverPlus\Builders\SearchRequestBuilder
     */
    public function build(SearchCriteriaQuery $query, int $page = null, int $perPage = null): SearchRequestBuilder;
}
