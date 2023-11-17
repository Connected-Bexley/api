<?php

namespace App\Docs\Schemas\Search;

use App\Models\Service;
use App\Search\ElasticSearch\ElasticsearchQueryBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreSearchSchema extends Schema
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
                Schema::string('persona'),
                Schema::string('wait_time')
                    ->enum(
                        Service::WAIT_TIME_ONE_WEEK,
                        Service::WAIT_TIME_TWO_WEEKS,
                        Service::WAIT_TIME_THREE_WEEKS,
                        Service::WAIT_TIME_MONTH,
                        Service::WAIT_TIME_LONGER
                    ),
                Schema::boolean('is_free'),
                Schema::string('order')
                    ->enum(ElasticsearchQueryBuilder::ORDER_RELEVANCE, ElasticsearchQueryBuilder::ORDER_DISTANCE)
                    ->default('relevance'),
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
