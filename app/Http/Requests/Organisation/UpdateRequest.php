<?php

namespace App\Http\Requests\Organisation;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Organisation;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use App\Rules\CanUpdateCategoryTaxonomyRelationships;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\NullableIf;
use App\Rules\RootTaxonomyIs;
use App\Rules\Slug;
use App\Rules\UkPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use HasMissingValues;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->isOrganisationAdmin($this->organisation)) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'string',
                'min:1',
                'max:255',
                Rule::unique(table(Organisation::class), 'slug')
                    ->ignoreModel($this->organisation),
                new Slug(),
            ],
            'name' => ['string', 'min:1', 'max:255'],
            'description' => ['string', 'min:1', 'max:10000'],
            'url' => [
                new NullableIf(function () {
                    return $this->user()->isGlobalAdmin();
                }),
                'url',
                'max:255'],
            'email' => [
                new NullableIf(function () {
                    return $this->user()->isGlobalAdmin() || $this->input('phone', $this->organisation->phone) !== null;
                }),
                'email',
                'max:255',
            ],
            'phone' => [
                new NullableIf(function () {
                    return $this->user()->isGlobalAdmin() || $this->input('email', $this->organisation->email) !== null;
                }),
                'string',
                'min:1',
                'max:255',
                new UkPhoneNumber('Organisation Phone - Please enter a valid UK telephone number.'),
            ],
            'logo_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG, File::MIME_TYPE_SVG),
                new FileIsPendingAssignment(),
            ],
            'social_medias' => ['array'],
            'social_medias.*' => ['array'],
            'social_medias.*.type' => [
                'required_with:social_medias.*',
                Rule::in([
                    SocialMedia::TYPE_TWITTER,
                    SocialMedia::TYPE_FACEBOOK,
                    SocialMedia::TYPE_INSTAGRAM,
                    SocialMedia::TYPE_YOUTUBE,
                    SocialMedia::TYPE_OTHER,
                ]),
            ],
            'social_medias.*.url' => ['required_with:social_medias.*', 'url', 'max:255'],
            'category_taxonomies' => ['array', new CanUpdateCategoryTaxonomyRelationships($this->user('api'), $this->organisation),
            ],
            'category_taxonomies.*' => [
                'exists:taxonomies,id',
                new RootTaxonomyIs(Taxonomy::NAME_CATEGORY),
            ],
        ];
    }

    /**
     * Check if the user requested only a preview of the update request.
     */
    public function isPreview(): bool
    {
        return $this->preview === true;
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        $urlMessage = 'Please enter a valid web address in the correct format (starting with https:// or http://).';

        return [
            'url.url' => $urlMessage,
            'social_medias.*.url' => $urlMessage,
            'email.email' => 'Please enter an email address users can use to contact your organisation.',
        ];
    }
}
