<?php

declare(strict_types=1);

namespace App\Search\ElasticSearch;

use App\Contracts\QueryBuilder;
use App\Models\Collection;
use App\Models\OrganisationEvent;
use App\Search\SearchCriteriaQuery;
use App\Support\Coordinate;
use Carbon\Carbon;
use ElasticScoutDriverPlus\Builders\SearchRequestBuilder;
use Illuminate\Support\Arr;

class EventQueryBuilder extends ElasticsearchQueryBuilder implements QueryBuilder
{
    /**
     * @var array
     */
    protected $esQuery;

    public function __construct()
    {
        $this->esQuery = [
            'function_score' => [
                'query' => [
                    'bool' => [
                        'must' => [],
                        'should' => [],
                        'filter' => [],
                    ],
                ],
                'functions' => [],
            ],
        ];

        $this->mustPath = 'function_score.query.bool.must';
        $this->shouldPath = 'function_score.query.bool.should';
        $this->filterPath = 'function_score.query.bool.filter';
    }

    public function build(SearchCriteriaQuery $query, int $page = null, int $perPage = null): SearchRequestBuilder
    {
        $page = page($page);
        $perPage = per_page($perPage);

        if ($query->hasQuery()) {
            $this->applyQuery($query->getQuery());
        }

        if ($query->hasCategories()) {
            $this->applyCategories($query->getCategories());
        }

        if ($query->hasIsFree()) {
            $this->applyIsFree($query->getIsFree());
        }

        if ($query->hasIsVirtual()) {
            $this->applyIsVirtual($query->getIsVirtual());
        }

        if ($query->hasHasWheelchairAccess()) {
            $this->applyHasWheelchairAccess($query->getHasWheelchairAccess());
        }

        if ($query->hasHasInductionLoop()) {
            $this->applyHasInductionLoop($query->getHasInductionLoop());
        }

        if ($query->hasHasAccessibleToilet()) {
            $this->applyHasAccessibleToilet($query->getHasAccessibleToilet());
        }

        if ($query->hasStartsAfter()) {
            $this->applyStartsAfter($query->getStartsAfter());
        }

        $this->applyEndsBefore($query->getEndsBefore());

        if ($query->hasLocation()) {
            $this->applyLocation($query->getLocation(), $query->getDistance());
        }

        $searchQuery = OrganisationEvent::searchQuery($this->esQuery)
            ->size($perPage)
            ->from(($page - 1) * $perPage);

        if ($query->hasOrder()) {
            $searchQuery->sortRaw($this->applyOrder($query));
        }

        return $searchQuery;
    }

    protected function applyQuery(string $query): void
    {
        $this->addMatch('title', $query, $this->shouldPath, 3);
        $this->addMatch('organisation_name', $query, $this->shouldPath, 3);
        $this->addMatch('intro', $query, $this->shouldPath, 2);
        $this->addMatch('description', $query, $this->shouldPath, 1.5);
        $this->addMatch('taxonomy_categories', $query, $this->shouldPath);

        $this->addMinimumShouldMatch();
    }

    protected function applyCategories(array $categorySlugs): void
    {
        $taxonomyNames = Collection::query()
            ->join('collection_taxonomies', 'collection_taxonomies.collection_id', '=', 'collections.id')
            ->join('taxonomies', 'collection_taxonomies.taxonomy_id', '=', 'taxonomies.id')
            ->whereIn('collections.slug', $categorySlugs)
            ->pluck('taxonomies.name')
            ->all();

        foreach ($taxonomyNames as $taxonomyName) {
            $this->addMatch('taxonomy_categories', $taxonomyName, $this->shouldPath);
        }

        $categoryNames = Collection::query()
            ->whereIn('slug', $categorySlugs)
            ->pluck('name')
            ->all();

        $this->addFilter('collection_categories', $categoryNames);
    }

    protected function applyIsFree(bool $isFree): void
    {
        $this->addFilter('is_free', $isFree);
    }

    protected function applyIsVirtual(bool $isVirtual): void
    {
        $this->addFilter('is_virtual', $isVirtual);
    }

    /**
     * @param string $startsAfter
     */
    public function applyStartsAfter(?string $startsAfter): void
    {
        if ($startsAfter) {
            $filters = Arr::get($this->esQuery, $this->filterPath);
            $filters[] = [
                'range' => [
                    'start_date' => ['gte' => Carbon::parse($startsAfter)->toDateTimeLocalString()],
                ],
            ];
            Arr::set($this->esQuery, $this->filterPath, $filters);
        }
    }

    /**
     * @param string $endsBefore
     */
    public function applyEndsBefore(?string $endsBefore): void
    {
        $filters = Arr::get($this->esQuery, $this->filterPath);
        if ($endsBefore) {
            $filters[] = [
                'range' => [
                    'end_date' => ['lte' => Carbon::parse($endsBefore)->toDateTimeLocalString()],
                ],
            ];
        } else {
            $filters[] = [
                'range' => [
                    'end_date' => ['gte' => 'now/d'],
                ],
            ];
        }
        Arr::set($this->esQuery, $this->filterPath, $filters);
    }

    protected function applyLocation(Coordinate $coordinate, ?int $distance): void
    {
        $filters = Arr::get($this->esQuery, $this->filterPath);
        // Add filter for listings within a search distance miles radius.
        $filters[] = [
            'nested' => [
                'path' => 'event_location',
                'query' => [
                    'geo_distance' => [
                        'distance' => $distance ? $distance . 'mi' : config('local.search_distance') . 'mi',
                        'event_location.location' => $coordinate->toArray(),
                    ],
                ],
            ],
        ];
        Arr::set($this->esQuery, $this->filterPath, $filters);

        // Apply scoring for favouring results closer to the coordinate.
        $this->esQuery['function_score']['functions'][] = [
            'gauss' => [
                'event_location.location' => [
                    'origin' => $coordinate->toArray(),
                    'scale' => '1mi',
                ],
            ],
        ];
    }

    protected function applyHasWheelchairAccess(bool $hasWheelchairAccess): void
    {
        $filters = Arr::get($this->esQuery, $this->filterPath);
        $filters[] = [
            'nested' => [
                'path' => 'event_location',
                'query' => [
                    'term' => [
                        'event_location.has_wheelchair_access' => $hasWheelchairAccess,
                    ],
                ],
            ],
        ];
        Arr::set($this->esQuery, $this->filterPath, $filters);
    }

    protected function applyHasInductionLoop(bool $hasInductionLoop): void
    {
        $filters = Arr::get($this->esQuery, $this->filterPath);
        $filters[] = [
            'nested' => [
                'path' => 'event_location',
                'query' => [
                    'term' => [
                        'event_location.has_induction_loop' => $hasInductionLoop,
                    ],
                ],
            ],
        ];
        Arr::set($this->esQuery, $this->filterPath, $filters);
    }

    protected function applyHasAccessibleToilet(bool $hasAccessibleToilet): void
    {
        $filters = Arr::get($this->esQuery, $this->filterPath);
        $filters[] = [
            'nested' => [
                'path' => 'event_location',
                'query' => [
                    'term' => [
                        'event_location.has_accessible_toilet' => $hasAccessibleToilet,
                    ],
                ],
            ],
        ];
        Arr::set($this->esQuery, $this->filterPath, $filters);
    }

    protected function applyOrder(SearchCriteriaQuery $query): array
    {
        $order = $query->getOrder();
        if ($order === static::ORDER_DISTANCE) {
            return [
                [
                    '_geo_distance' => [
                        'event_location.location' => $query->getLocation()->toArray(),
                        'nested_path' => 'event_location',
                    ],
                ],
            ];
        } elseif ($order === static::ORDER_START) {
            return [
                [
                    'start_date' => [
                        'order' => 'asc',
                    ],
                ],
            ];
        } elseif ($order === static::ORDER_END) {
            return [
                [
                    'end_date' => [
                        'order' => 'asc',
                    ],
                ],
            ];
        }

        return ['_score'];
    }

    protected function addMinimumShouldMatch()
    {
        $bool = Arr::get($this->esQuery, 'function_score.query.bool');
        if (empty($bool['minimum_should_match'])) {
            $bool['minimum_should_match'] = 1;
        } else {
            $bool['minimum_should_match']++;
        }
        Arr::set($this->esQuery, 'function_score.query.bool', $bool);
    }
}
