<?php

namespace App\Http\Requests\OrganisationEvent;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Taxonomy;
use App\Models\UserRole;
use App\Rules\DateSanity;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\RootTaxonomyIs;
use App\Rules\Slug;
use App\Rules\UkPhoneNumber;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use HasMissingValues;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->isOrganisationAdmin(Organisation::find($this->organisation_id))) {
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
            'organisation_id' => [
                'required',
                'exists:organisations,id',
                function ($attribute, $value, $fail) {
                    if (!$this->user('api')->isGlobalAdmin() && !$this->user('api')->isOrganisationAdmin(Organisation::findOrFail($value))) {
                        $fail('The organisation_id field must contain an ID for an organisation you are an organisation admin for.');
                    }
                },
            ],
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'slug' => ['string', 'min:1', 'max:255', new Slug()],
            'start_date' => ['required', 'date_format:Y-m-d', new DateSanity($this)],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', new DateSanity($this)],
            'start_time' => ['required', 'date_format:H:i:s', new DateSanity($this)],
            'end_time' => ['required', 'date_format:H:i:s', new DateSanity($this)],
            'intro' => ['required', 'string', 'min:1', 'max:300'],
            'description' => [
                'required',
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(config('local.event_description_max_chars'), 'Description tab - The long description must be ' . config('local.event_description_max_chars') . ' characters or fewer.'),
            ],
            'is_free' => ['required', 'boolean'],
            'fees_text' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                'required_if:is_free,false',
            ],
            'fees_url' => [
                'nullable',
                'url',
                'min:1',
                'max:255',
            ],
            'organiser_name' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                Rule::requiredIf(function () {
                    return !empty($this->organiser_phone) || !empty($this->organiser_email) || !empty($this->organiser_url);
                }),
            ],
            'organiser_phone' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                new UkPhoneNumber(),
                Rule::requiredIf(function () {
                    return !empty($this->organiser_name) && empty($this->organiser_email) && empty($this->organiser_url);
                }),
            ],
            'organiser_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::requiredIf(function () {
                    return !empty($this->organiser_name) && empty($this->organiser_phone) && empty($this->organiser_url);
                }),
            ],
            'organiser_url' => [
                'nullable',
                'url',
                'max:255',
                Rule::requiredIf(function () {
                    return !empty($this->organiser_name) && empty($this->organiser_email) && empty($this->organiser_phone);
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
                'required',
                'boolean',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    false
                ),
            ],
            'is_virtual' => ['required', 'boolean'],
            'location_id' => [
                'nullable',
                Rule::requiredIf(!$this->is_virtual),
                'exists:locations,id',
            ],
            'image_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG, File::MIME_TYPE_SVG),
                new FileIsPendingAssignment(),
            ],
            'category_taxonomies' => [
                'present',
                'array',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    []
                ),
            ],
            'category_taxonomies.*' => ['exists:taxonomies,id', new RootTaxonomyIs(Taxonomy::NAME_CATEGORY)],
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
            'organisation_id.required' => 'Please select the name of your organisation from the dropdown list.',
            'fees_url.url' => $urlMessage,
            'organiser_url.url' => $urlMessage,
            'booking_url.url' => $urlMessage,
            'organiser_email.email' => 'Please enter an email address users can use to contact your event organiser.',
        ];
    }
}
