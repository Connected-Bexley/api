<?php

namespace App\Http\Requests\Service;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Role;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\UserRole;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\InOrder;
use App\Rules\IsOrganisationAdmin;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\RootTaxonomyIs;
use App\Rules\Slug;
use App\Rules\UkPhoneNumber;
use App\Rules\UserHasRole;
use App\Rules\VideoEmbed;
use Carbon\CarbonImmutable;
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
        if ($this->user()->isOrganisationAdmin()) {
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
            'organisation_id' => ['required', 'exists:organisations,id', new IsOrganisationAdmin($this->user('api'))],
            'slug' => ['required', 'string', 'min:1', 'max:255', new Slug()],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'type' => [
                'required',
                Rule::in([
                    Service::TYPE_SERVICE,
                    Service::TYPE_ACTIVITY,
                    Service::TYPE_CLUB,
                    Service::TYPE_GROUP,
                ]),
            ],
            'status' => [
                'required',
                Rule::in([
                    Service::STATUS_ACTIVE,
                    Service::STATUS_INACTIVE,
                ]),
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    Service::STATUS_INACTIVE
                ),
            ],
            'intro' => ['required', 'string', 'min:1', 'max:300'],
            'description' => [
                'required',
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(3000, 'Description tab - The long description must be 3000 characters or fewer.'),
            ],
            'wait_time' => ['present', 'nullable', Rule::in([
                Service::WAIT_TIME_ONE_WEEK,
                Service::WAIT_TIME_TWO_WEEKS,
                Service::WAIT_TIME_THREE_WEEKS,
                Service::WAIT_TIME_MONTH,
                Service::WAIT_TIME_LONGER,
            ])],
            'is_free' => ['required', 'boolean'],
            'fees_text' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'fees_url' => ['present', 'nullable', 'url', 'max:255'],
            'testimonial' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'video_embed' => ['present', 'nullable', 'url', 'max:255', new VideoEmbed()],
            'url' => ['required', 'url', 'max:255'],
            'contact_name' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'contact_phone' => [
                'present',
                'nullable',
                'string',
                'min:1',
                'max:255',
                new UkPhoneNumber('Service Contact Phone - Please enter a valid UK telephone number.'),
            ],
            'contact_email' => ['present', 'nullable', 'email', 'max:255'],
            'cqc_location_id' => ['present_if_flagged', 'nullable', 'string', 'regex:/^\d\-\d+$/'],
            'show_referral_disclaimer' => [
                'required',
                'boolean',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::superAdmin()->id,
                    ]),
                    ($this->referral_method === Service::REFERRAL_METHOD_NONE) ? false : true
                ),
            ],
            'referral_method' => [
                'required',
                Rule::in([
                    Service::REFERRAL_METHOD_INTERNAL,
                    Service::REFERRAL_METHOD_EXTERNAL,
                    Service::REFERRAL_METHOD_NONE,
                ]),
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    Service::REFERRAL_METHOD_NONE
                ),
            ],
            'referral_button_text' => [
                'present',
                'nullable',
                'string',
                'min:1',
                'max:255',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    null
                ),
            ],
            'referral_email' => [
                'required_if:referral_method,' . Service::REFERRAL_METHOD_INTERNAL,
                'present',
                'nullable',
                'email',
                'max:255',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    null
                ),
            ],
            'referral_url' => [
                'required_if:referral_method,' . Service::REFERRAL_METHOD_EXTERNAL,
                'present',
                'nullable',
                'url',
                'max:255',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::globalAdmin()->id,
                    ]),
                    null
                ),
            ],
            'ends_at' => ['present', 'nullable', 'date_format:' . CarbonImmutable::ISO8601],
            'useful_infos' => ['present', 'array'],
            'useful_infos.*' => ['array'],
            'useful_infos.*.title' => ['required_with:useful_infos.*', 'string', 'min:1', 'max:255'],
            'useful_infos.*.description' => ['required_with:useful_infos.*', 'string', new MarkdownMinLength(1), new MarkdownMaxLength(10000)],
            'useful_infos.*.order' => ['required_with:useful_infos.*', 'integer', 'min:1', new InOrder(array_pluck_multi($this->useful_infos, 'order'))],

            'offerings' => ['present', 'array'],
            'offerings.*' => ['array'],
            'offerings.*.offering' => ['required_with:offerings.*', 'string', 'min:1', 'max:255'],
            'offerings.*.order' => ['required_with:offerings.*', 'integer', 'min:1', new InOrder(array_pluck_multi($this->offerings, 'order'))],

            'social_medias' => ['nullable', 'size:0'],

            'gallery_items' => ['present', 'array'],
            'gallery_items.*' => ['array'],
            'gallery_items.*.file_id' => [
                'required_with:gallery_items.*',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG),
                new FileIsPendingAssignment(),
            ],

            'tags' => [
                'present_if_flagged',
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
            'tags.*' => ['array'],
            'tags.*.slug' => ['required_with:tags.*', 'string', 'min:1', 'max:255', new Slug()],
            'tags.*.label' => ['required_with:tags.*', 'string', 'min:1', 'max:255'],

            'category_taxonomies' => $this->categoryTaxonomiesRules(),
            'category_taxonomies.*' => ['exists:taxonomies,id', new RootTaxonomyIs(Taxonomy::NAME_CATEGORY)],
            'logo_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG),
                new FileIsPendingAssignment(),
            ],
            'score' => [
                'nullable',
                'numeric',
                Rule::in([
                    Service::SCORE_POOR,
                    Service::SCORE_BELOW_AVERAGE,
                    Service::SCORE_AVERAGE,
                    Service::SCORE_ABOVE_AVERAGE,
                    Service::SCORE_EXCELLENT,
                ]),
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::superAdmin()->id,
                    ]),
                    null
                ),
            ],
            'eligibility_types' => ['array'],
            'eligibility_types.taxonomies' => ['array'],
            'eligibility_types.taxonomies.*' => [
                'uuid',
                'exists:taxonomies,id',
                new RootTaxonomyIs(Taxonomy::NAME_SERVICE_ELIGIBILITY),
            ],
            'eligibility_types.custom' => ['array'],
            'eligibility_types.custom.age_group' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.disability_custom' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.gender' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.income' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.language' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.ethnicity' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.housing' => ['nullable', 'string', 'min:1', 'max:255'],
            'eligibility_types.custom.other' => ['nullable', 'string', 'min:1', 'max:255'],
        ];
    }

    /**
     * @return array
     */
    protected function categoryTaxonomiesRules(): array
    {
        // If global admin and above.
        if ($this->user()->isGlobalAdmin()) {
            return ['required', 'array'];
        }

        // If not a global admin.
        return ['present', 'array', 'size:0'];
    }

    /**
     * {@inheritDoc}
     */
    public function messages()
    {
        $type = $this->input('type', Service::TYPE_SERVICE);

        return [
            'organisation_id.required' => 'Details tab - Please select the name of your organisation from the dropdown list.',
            'slug.required' => "Details tab -  Please enter a 'unique slug'.",
            'name.required' => "Details tab - Please enter the name of your {$type}.",
            'intro.required' => "Description tab - Please enter a brief description of the {$type}.",
            'description.required' => "Description tab - Please enter all the information someone should know about your {$type}.",
            'is_free.required' => "Additional info tab - Please provide more information about the cost of the {$type}.",
            'url.required' => "Details Tab - Please provide the web address for your {$type}.",
            'url.url' => 'Details tab - Please enter a valid web address in the correct format (starting with https:// or http://).',
            'category_taxonomies.required' => 'Taxonomy tab - Please select at least one relevant taxonomy.',
            'video_embed.url' => 'Additional info tab - Please enter a valid video link (eg. https://www.youtube.com/watch?v=JyHR_qQLsLM).',
            'contact_email.email' => "Additional Info tab -  Please enter an email address users can use to contact your {$type} (eg. name@example.com).",
            'useful_infos.*.title.required_with' => 'Good to know tab - Please select a title.',
            'useful_infos.*.description.required_with' => 'Good to know tab - Please enter a description.',
        ];
    }

    public function isPreview(): bool
    {
        return $this->preview === true;
    }
}
