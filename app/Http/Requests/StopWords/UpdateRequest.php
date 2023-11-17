<?php

namespace App\Http\Requests\StopWords;

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
            'stop_words' => ['present', 'array'],
            'stop_words.*' => ['string', 'max:255'],
        ];
    }
}
