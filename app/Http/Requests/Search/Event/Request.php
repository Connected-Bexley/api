<?php

namespace App\Http\Requests\Search\Event;

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
            'is_free' => [
                'boolean',
            ],
            'is_virtual' => [
                'boolean',
            ],
            'has_wheelchair_access' => [
                'boolean',
            ],
            'has_induction_loop' => [
                'boolean',
            ],
            'starts_after' => [
                'date',
            ],
            'ends_before' => [
                'date',
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
            'order' => [
                Rule::in([
                    ElasticsearchQueryBuilder::ORDER_RELEVANCE,
                    ElasticsearchQueryBuilder::ORDER_DISTANCE,
                    ElasticsearchQueryBuilder::ORDER_START,
                    ElasticsearchQueryBuilder::ORDER_END,
                ]), ],
        ];
    }
}
