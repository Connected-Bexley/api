<?php

namespace App\Http\Requests\Organisation;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Organisation;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\RootTaxonomyIs;
use App\Rules\Slug;
use App\Rules\UkPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use HasMissingValues;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()->isGlobalAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'slug' => [
                'required',
                'string',
                'min:1',
                'max:255',
                'unique:' . table(Organisation::class) . ',slug',
                new Slug(),
            ],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'description' => ['required', 'string', 'min:1', 'max:10000'],
            'url' => ['present', 'nullable', 'url', 'max:255'],
            'email' => ['present', 'nullable', 'email', 'max:255'],
            'phone' => [
                'present',
                'nullable',
                'string',
                'min:1',
                'max:255',
                new UkPhoneNumber('Organisation Phone - Please enter a valid UK telephone number.'),
            ],
            'logo_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG),
                new FileIsPendingAssignment(),
            ],
            'social_medias' => ['sometimes', 'array'],
            'social_medias.*' => ['array'],
            'social_medias.*.type' => ['required_with:social_medias.*', Rule::in([
                SocialMedia::TYPE_TWITTER,
                SocialMedia::TYPE_FACEBOOK,
                SocialMedia::TYPE_INSTAGRAM,
                SocialMedia::TYPE_YOUTUBE,
                SocialMedia::TYPE_OTHER,
            ])],
            'social_medias.*.url' => ['required_with:social_medias.*', 'url', 'max:255'],
            'category_taxonomies' => ['present', 'array'],
            'category_taxonomies.*' => ['exists:taxonomies,id', new RootTaxonomyIs(Taxonomy::NAME_CATEGORY)],
        ];
    }
}
