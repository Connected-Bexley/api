<?php

namespace App\Http\Requests\Service\Related;

use Illuminate\Foundation\Http\FormRequest;

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
            'location' => ['array'],
            'location.lat' => ['required_with:location', 'numeric', 'min:-90', 'max:90'],
            'location.lon' => ['required_with:location', 'numeric', 'min:-180', 'max:180'],
        ];
    }
}
