<?php

namespace App\Http\Requests\Thesaurus;

use App\Rules\Synonyms;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('api')->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'synonyms' => ['present', 'array'],
            'synonyms.*' => ['present', 'array', new Synonyms()],
            'synonyms.*.*' => ['string'],
        ];
    }
}
