<?php

namespace App\Http\Requests\Setting\BannerImage;

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
     * {@inheritDoc}
     */
    protected function extraRules(): array
    {
        return [
            //
        ];
    }
}
