<?php

namespace App\Docs\Schemas\Search;

use App\Search\ElasticSearch\ElasticsearchQueryBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreEventSearchSchema extends Schema
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::integer('page'),
                Schema::integer('per_page')
                    ->default(config('local.pagination_results')),
                Schema::string('query'),
                Schema::string('category'),
                Schema::boolean('is_free'),
                Schema::boolean('is_virtual'),
                Schema::boolean('has_wheelchair_access'),
                Schema::boolean('has_induction_loop'),
                Schema::boolean('has_accessible_toilet'),
                Schema::string('starts_after'),
                Schema::string('ends_before'),
                Schema::string('order')
                    ->enum(ElasticsearchQueryBuilder::ORDER_RELEVANCE, ElasticsearchQueryBuilder::ORDER_DISTANCE, ElasticsearchQueryBuilder::ORDER_START, ElasticsearchQueryBuilder::ORDER_END)
                    ->default(ElasticsearchQueryBuilder::ORDER_START),
                Schema::object('location')
                    ->required('lat', 'lon')
                    ->properties(
                        Schema::number('lat')
                            ->type(Schema::FORMAT_FLOAT),
                        Schema::number('lon')
                            ->type(Schema::FORMAT_FLOAT)
                    ),
                Schema::integer('distance')
                    ->default(config('local.search_distance')),
                Schema::array('eligibilities')
                    ->items(
                        Schema::string()
                    )
            );
    }
}
