<?php

namespace App\Http\Requests\Search;

use App\Models\Service;
use App\Search\ElasticSearch\ElasticsearchQueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'query' => [
                'string',
                'min:3',
                'max:255',
            ],
            'category' => [
                'string',
                'min:1',
                'max:255',
            ],
            'persona' => [
                'string',
                'min:1',
                'max:255',
            ],
            'wait_time' => [
                Rule::in([
                    Service::WAIT_TIME_ONE_WEEK,
                    Service::WAIT_TIME_TWO_WEEKS,
                    Service::WAIT_TIME_THREE_WEEKS,
                    Service::WAIT_TIME_MONTH,
                    Service::WAIT_TIME_LONGER,
                ]),
            ],
            'is_free' => [
                'required_without_all:query,category,persona,wait_time,location,eligibilities',
                'boolean',
            ],
            'order' => [
                Rule::in([ElasticsearchQueryBuilder::ORDER_RELEVANCE, ElasticsearchQueryBuilder::ORDER_DISTANCE]),
            ],
            'location' => [
                'required_if:order,distance',
                'array',
            ],
            'location.lat' => [
                'required_with:location',
                'numeric',
                'min:-90',
                'max:90',
            ],
            'location.lon' => [
                'required_with:location',
                'numeric',
                'min:-180',
                'max:180',
            ],
            'distance' => [
                'integer',
                'min:0',
            ],
            'eligibilities' => [
                'array',
            ],
            'eligibilities.*' => ['string'],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }
}
