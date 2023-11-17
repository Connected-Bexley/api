<?php

namespace App\Http\Requests\Service\Refresh;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
        if (optional($this->user('api'))->isServiceAdmin($this->service)) {
            return [];
        }

        return [
            'token' => ['required', 'exists:service_refresh_tokens,id'],
        ];
    }
}
