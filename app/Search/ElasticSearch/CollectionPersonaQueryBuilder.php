<?php

declare(strict_types=1);

namespace App\Search\ElasticSearch;

use App\Contracts\QueryBuilder;
use App\Models\Collection;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Search\SearchCriteriaQuery;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use Illuminate\Support\Arr;

class CollectionPersonaQueryBuilder extends ElasticsearchQueryBuilder implements QueryBuilder
{
    public function __construct()
    {
        $this->esQuery = [
            'function_score' => [
                'query' => [
                    'bool' => [
                        'should' => [],
                        'filter' => [],
                    ],
                ],
                'functions' => [
                    [
                        'field_value_factor' => [
                            'field' => 'score',
                            'missing' => 1,
                            'modifier' => 'ln1p',
                        ],
                    ],
                ],
                'boost_mode' => 'multiply',
            ],
        ];

        $this->filterPath = 'function_score.query.bool.filter';
    }

    public function build(SearchCriteriaQuery $query, int $page = null, int $perPage = null): SearchRequestBuilder
    {
        $page = page($page);
        $perPage = per_page($perPage);

        $this->applyStatus(Service::STATUS_ACTIVE);
        $this->applyPersona($query->getPersonas()[0]);

        return Service::searchQuery($this->esQuery)
            ->size($perPage)
            ->from(($page - 1) * $perPage);
    }

    protected function applyStatus(string $status): void
    {
        $this->addFilter('status', $status);
    }

    protected function applyPersona(string $personaSlug): void
    {
        $persona = Collection::query()
            ->with('taxonomies')
            ->where('slug', '=', $personaSlug)
            ->first();

        $this->addFilter('collection_personas', $persona->getAttribute('name'));

        $should = Arr::get($this->esQuery, 'function_score.query.bool.should');

        $persona->taxonomies->each(function (Taxonomy $taxonomy) use ($should): void {
            $should[] = [
                'term' => [
                    'taxonomy_categories.keyword' => $taxonomy->getAttribute('name'),
                ],
            ];
        });
        Arr::set($this->esQuery, 'function_score.query.bool.should', $should);
    }
}
