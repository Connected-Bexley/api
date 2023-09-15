<?php

declare (strict_types=1);

namespace App\Search\ElasticSearch;

use Illuminate\Support\Arr;

abstract class ElasticsearchQueryBuilder
{
    const ORDER_RELEVANCE = 'relevance';

    const ORDER_DISTANCE = 'distance';

    const ORDER_START = 'start_date';

    const ORDER_END = 'end_date';

    /**
     * Elasticsearch query array.
     *
     * @var array
     */
    protected $esQuery;

    /**
     * Path to add must queries.
     *
     * @var string
     */
    protected $mustPath;

    /**
     * Path to add should queries.
     *
     * @var string
     */
    protected $shouldPath;

    /**
     * Path to add filter queries.
     *
     * @var string
     */
    protected $filterPath;

    /**
     * Add a match query.
     *
     * @param string $field
     * @param string $term
     * @param int $boost
     * @param mixed $fuzziness
     * @param string $operator
     */
    protected function addMatch(string $field, string $term, string $path = null, $boost = 1, $fuzziness = 'AUTO', $operator = 'OR'): void
    {
        $path = $path ?? $this->mustPath;
        $matches = Arr::get($this->esQuery, $path);
        $matches[] = [
            'match' => [
                $field => [
                    'query' => $term,
                    'boost' => $boost,
                    'fuzziness' => $fuzziness,
                    'operator' => $operator,
                ],
            ],
        ];
        Arr::set($this->esQuery, $path, $matches);
    }

    /**
     * Add a match_phrase query.
     *
     * @param string $field
     * @param string $term
     * @param int $boost
     */
    protected function addMatchPhrase(string $field, string $term, string $path = null, $boost = 1): void
    {
        $path = $path ?? $this->mustPath;
        $matches = Arr::get($this->esQuery, $path);
        $matches[] = [
            'match_phrase' => [
                $field => [
                    'query' => $term,
                    'boost' => $boost,
                ],
            ],
        ];
        Arr::set($this->esQuery, $path, $matches);
    }

    /**
     * Add a term query.
     *
     * @param string $field
     * @param string $term
     * @param int $boost
     */
    protected function addTerm(string $field, string $term, string $path = null, $boost = 1): void
    {
        $path = $path ?? $this->mustPath;
        $matches = Arr::get($this->esQuery, $path);
        $matches[] = [
            'term' => [
                $field => [
                    'value' => $term,
                    'boost' => $boost,
                ],
            ],
        ];
        Arr::set($this->esQuery, $path, $matches);
    }

    /**
     * Add a terms query.
     *
     * @param string $field
     * @param string $term
     * @param int $boost
     */
    protected function addTerms(string $field, array $terms, string $path = null, $boost = 1): void
    {
        $path = $path ?? $this->mustPath;
        $matches = Arr::get($this->esQuery, $path);
        $matches[] = [
            'terms' => [
                $field => $terms,
                'boost' => $boost,
            ],
        ];
        Arr::set($this->esQuery, $path, $matches);
    }

    /**
     * Add a filter.
     *
     * @param string $field
     * @param mixed $value
     */
    public function addFilter(string $field, $value): void
    {
        $filters = Arr::get($this->esQuery, $this->filterPath);
        $type = is_array($value) ? 'terms' : 'term';
        $filters[] = [
            $type => [
                $field => $value,
            ],
        ];
        Arr::set($this->esQuery, $this->filterPath, $filters);
    }
}
