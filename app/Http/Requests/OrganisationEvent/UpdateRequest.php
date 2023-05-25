<?php

namespace App\Http\Requests\OrganisationEvent;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Role;
use App\Models\Taxonomy;
use App\Models\UserRole;
use App\Rules\CanUpdateCategoryTaxonomyRelationships;
use App\Rules\DateSanity;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\NullableIf;
use App\Rules\RootTaxonomyIs;
use App\Rules\UkPhoneNumber;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use HasMissingValues;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()->isOrganisationAdmin($this->organisation_event->organisation)) {
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
            'title' => ['string', 'min:1', 'max:255'],
            'start_date' => ['date_format:Y-m-d', 'after:today', new DateSanity($this)],
            'end_date' => ['date_format:Y-m-d', new DateSanity($this)],
            'start_time' => ['date_format:H:i:s', new DateSanity($this)],
            'end_time' => ['date_format:H:i:s', new DateSanity($this)],
            'intro' => ['string', 'min:1', 'max:300'],
            'description' => [
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(3000, 'Description tab - The long description must be 3000 characters or fewer.'),
            ],
            'is_free' => ['boolean'],
            'fees_text' => ['nullable', 'string', 'min:1', 'max:255', 'required_if:is_free,false'],
            'fees_url' => ['nullable', 'url', 'max:255'],
            'organiser_name' => [
                'string',
                'min:1',
                'max:255',
                Rule::requiredIf(function () {
                    return !empty($this->organiser_phone) || !empty($this->organiser_email) || !empty($this->organiser_url);
                }),
                new NullableIf(function () {
                    return empty($this->organiser_phone) && empty($this->organiser_email) && empty($this->organiser_url);
                }),
            ],
            'organiser_phone' => [
                'string',
                'min:1',
                'max:255',
                new UkPhoneNumber(),
                Rule::requiredIf(function () {
                    return !empty($this->organiser_name) && empty($this->organiser_email) && empty($this->organiser_url);
                }),
                new NullableIf(function () {
                    return empty($this->organiser_name);
                }),
            ],
            'organiser_email' => [
                'email',
                'max:255',
                Rule::requiredIf(function () {
                    return !empty($this->organiser_name) && empty($this->organiser_phone) && empty($this->organiser_url);
                }),
                new NullableIf(function () {
                    return empty($this->organiser_name);
                }),
            ],
            'organiser_url' => [
                'url',
                'max:255',
                Rule::requiredIf(function () {
                    return !empty($this->organiser_name) && empty($this->organiser_email) && empty($this->organiser_phone);
                }),
                new NullableIf(function () {
                    return empty($this->organiser_name);
                }),
            ],
            'booking_title' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                'required_with:booking_summary,booking_url,booking_cta',
            ],
            'booking_summary' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                'required_with:booking_title,booking_url,booking_cta',
            ],
            'booking_url' => [
                'nullable',
                'url',
                'min:1',
                'max:255',
                'required_with:booking_summary,booking_title,booking_cta',
            ],
            'booking_cta' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                'required_with:booking_summary,booking_url,booking_title',
            ],
            'homepage' => [
                'boolean',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    $this->organisation_event->homepage
                ),
            ],
            'is_virtual' => ['boolean'],
            'location_id' => [
                Rule::requiredIf(function () {
                    return !empty($this->is_virtual) && $this->is_virtual == false;
                }),
                'exists:locations,id',
            ],
            'image_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG),
                new FileIsPendingAssignment(),
            ],
            'category_taxonomies' => [
                'array',
                new CanUpdateCategoryTaxonomyRelationships($this->user('api'), $this->organisation_event),
            ],
            'category_taxonomies.*' => [
                'exists:taxonomies,id',
                new RootTaxonomyIs(Taxonomy::NAME_CATEGORY),
            ],
        ];
    }

    /**
     * Check if the user requested only a preview of the update request.
     *
     * @return bool
     */
    public function isPreview(): bool
    {
        return $this->preview === true;
    }

    /**
     * {@inheritDoc}
     */
    public function messages()
    {
        $urlMessage = 'Please enter a valid web address in the correct format (starting with https:// or http://).';

        return [
            'fees_url.url' => $urlMessage,
            'organiser_url.url' => $urlMessage,
            'booking_url.url' => $urlMessage,
            'organiser_email.email' => 'Please enter an email address users can use to contact your event organiser.',
        ];
    }
}
