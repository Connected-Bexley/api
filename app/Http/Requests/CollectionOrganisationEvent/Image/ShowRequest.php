<?php

namespace App\Http\Requests\CollectionOrganisationEvent\Image;

use App\Http\Requests\ImageFormRequest;

class ShowRequest extends ImageFormRequest
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
    public function extraRules(): array
    {
        return [
            //
        ];
    }
}
