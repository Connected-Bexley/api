<?php

namespace App\Http\Requests\OrganisationSignUpForm;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\SocialMedia;
use App\Rules\InOrder;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\Password;
use App\Rules\Slug;
use App\Rules\UkMobilePhoneNumber;
use App\Rules\UkPhoneNumber;
use App\Rules\UserEmailNotInPendingSignupRequest;
use App\Rules\UserEmailNotTaken;
use App\Rules\VideoEmbed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'user' => ['required', 'array'],
            'user.first_name' => ['required', 'string', 'min:1', 'max:255'],
            'user.last_name' => ['required', 'string', 'min:1', 'max:255'],
            'user.email' => [
                'required',
                'email',
                'max:255',
                new UserEmailNotTaken(null, 'User Email - The email entered is already in use. Please enter a different email address or log into your existing account.'),
                new UserEmailNotInPendingSignupRequest('User Email - The email has already been submitted for approval. Please await the outcome of the approval process before registering with this email.'),
            ],
            'user.phone' => [
                'required',
                'string',
                'min:1',
                'max:255',
                new UkMobilePhoneNumber('User Phone - Please enter a valid UK mobile telephone number.'),
            ],
            'user.password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                new Password('User Password - Please create a password that is at least eight characters long, contain one uppercase letter, one lowercase letter, one number and one special character (!"#$%&\'()*+,-./:;<=>?@[]^_`{|}~)'),
            ],

            'organisation' => ['required', 'array'],
            'organisation.id' => [
                'required_without_all:organisation.slug,organisation.name,organisation.description,organisation.url',
                'nullable',
                'exists:organisations,id',
            ],
            'organisation.slug' => [
                'required_without:organisation.id',
                'nullable',
                'string',
                'min:1',
                'max:255',
                Rule::unique(table(Organisation::class), 'slug')
                    ->ignore($this->input('organisation.id')),
                new Slug(),
            ],
            'organisation.name' => ['required_without:organisation.id', 'nullable', 'string', 'min:1', 'max:255'],
            'organisation.description' => [
                'required_without:organisation.id',
                'nullable',
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(config('local.organisation_description_max_chars'), 'Description tab - The long description must be ' . config('local.organisation_description_max_chars') . ' characters or fewer.'),
            ],
            'organisation.url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'organisation.email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],
            'organisation.phone' => [
                'sometimes',
                'nullable',
                'string',
                'min:1',
                'max:255',
                new UkPhoneNumber('Organisation Phone - Please enter a valid UK telephone number.'),
            ],

            'service' => ['sometimes', 'array'],
            'service.slug' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:255',
                'unique:' . table(Service::class) . ',slug',
                new Slug(),
            ],
            'service.name' => ['sometimes', 'required', 'string', 'min:1', 'max:255'],
            'service.type' => [
                'sometimes',
                'required',
                Rule::in([
                    Service::TYPE_SERVICE,
                    Service::TYPE_ACTIVITY,
                    Service::TYPE_CLUB,
                    Service::TYPE_GROUP,
                ]),
            ],
            'service.intro' => ['sometimes', 'required', 'string', 'min:1', 'max:300'],
            'service.description' => [
                'sometimes',
                'required',
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(config('local.service_description_max_chars'), 'Description tab - The long description must be ' . config('local.service_description_max_chars') . ' characters or fewer.'),
            ],
            'service.wait_time' => ['sometimes', 'present', 'nullable', Rule::in([
                Service::WAIT_TIME_ONE_WEEK,
                Service::WAIT_TIME_TWO_WEEKS,
                Service::WAIT_TIME_THREE_WEEKS,
                Service::WAIT_TIME_MONTH,
                Service::WAIT_TIME_LONGER,
            ])],
            'service.is_free' => ['sometimes', 'required', 'boolean'],
            'service.fees_text' => ['sometimes', 'present', 'nullable', 'string', 'min:1', 'max:255'],
            'service.fees_url' => ['sometimes', 'present', 'nullable', 'url', 'max:255'],
            'service.testimonial' => ['sometimes', 'present', 'nullable', 'string', 'min:1', 'max:255'],
            'service.video_embed' => ['sometimes', 'present', 'nullable', 'url', 'max:255', new VideoEmbed()],
            'service.url' => ['sometimes', 'present', 'nullable', 'url', 'max:255'],
            'service.contact_name' => ['sometimes', 'present', 'nullable', 'string', 'min:1', 'max:255'],
            'service.contact_phone' => ['sometimes', 'present', 'nullable', 'string', 'min:1', 'max:255'],
            'service.contact_email' => ['sometimes', 'present', 'nullable', 'email', 'max:255'],
            'service.useful_infos' => ['sometimes', 'present', 'array'],
            'service.useful_infos.*' => ['sometimes', 'array'],
            'service.useful_infos.*.title' => [
                'required_with:service.useful_infos.*',
                'string',
                'min:1',
                'max:255',
            ],
            'service.useful_infos.*.description' => [
                'required_with:service.useful_infos.*',
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(config('local.useful_info_description_max_chars')),
            ],
            'service.useful_infos.*.order' => [
                'required_with:service.useful_infos.*',
                'integer',
                'min:1',
                new InOrder(array_pluck_multi($this->input('service.useful_infos', []), 'order')),
            ],
            'service.offerings' => ['sometimes', 'present', 'array'],
            'service.offerings.*' => ['sometimes', 'array'],
            'service.offerings.*.offering' => [
                'required_with:service.offerings.*',
                'string',
                'min:1',
                'max:255',
            ],
            'service.offerings.*.order' => [
                'required_with:service.offerings.*',
                'integer',
                'min:1',
                new InOrder(array_pluck_multi($this->input('service.offerings', []), 'order')),
            ],
            'service.social_medias' => ['sometimes', 'present', 'array'],
            'service.social_medias.*' => ['sometimes', 'array'],
            'service.social_medias.*.type' => ['required_with:service.social_medias.*', Rule::in([
                SocialMedia::TYPE_TWITTER,
                SocialMedia::TYPE_FACEBOOK,
                SocialMedia::TYPE_INSTAGRAM,
                SocialMedia::TYPE_YOUTUBE,
                SocialMedia::TYPE_OTHER,
            ])],
            'service.social_medias.*.url' => [
                'required_with:service.social_medias.*',
                'url',
                'max:255',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        $type = $this->get('service.type', Service::TYPE_SERVICE);

        return [
            'user.first_name.required' => '2. User account - Please enter your first name.',
            'user.last_name.required' => '2. User account - Please enter your last name.',
            'user.email.required' => '2. User account - Please enter your email address.',
            'user.email.email' => '2. User account - Please enter an email address in the correct format (eg. name@example.com).',
            'user.phone.required' => '2. User account - Please enter your phone number.',
            'user.password.required' => '2. User account - Please enter a password.',
            'user.password.min' => '2. User account - Please create a password that is at least eight characters long.',

            'organisation.slug.required' => '3. Organisation - Please enter the organisation slug.',
            'organisation.slug.unique' => '3. Organisation - The organisation is already listed. Please contact us for help logging in ' . config('local.global_admin.email') . '.',
            'organisation.name.required' => '3. Organisation - Please enter the organisation name.',
            'organisation.description.required' => '3. Organisation - Please enter a one-line summary of the organisation.',
            'organisation.url.url' => '3. Organisation - Please enter a valid web address in the correct format (starting with https:// or http://).',
            'organisation.email.email' => '3. Organisation - Please enter the email for your organisation (eg. name@example.com).',

            'service.slug.required' => "4. Service, Details tab - Please enter the name of your {$type}.",
            'service.name.required' => "4. Service, Details tab - Please enter the name of your {$type}.",
            'service.video_embed.url' => '4. Service, Additional info tab - Please enter a valid video link (eg. https://www.youtube.com/watch?v=JyHR_qQLsLM).',
            'service.intro.required' => "4. Service, Description tab - Please enter a brief description of the {$type}.",
            'service.description.required' => "4. Service, Description tab - Please enter all the information someone should know about your {$type}.",
            'service.url.url' => '4. Service, Details tab - Please enter a valid web address in the correct format (starting with https:// or http://).',
            'service.contact_email.email' => "4. Service, Additional Info tab - Please enter an email address users can use to contact your {$type} (eg. name@example.com).",
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user;
        $user['phone'] = Str::remove(' ', $user['phone']);

        $organisation = $this->organisation;
        if ($organisation['phone'] ?? false) {
            $organisation['phone'] = Str::remove(' ', $organisation['phone']);
        }

        $this->merge([
            'user' => $user,
            'organisation' => $organisation,
        ]);
    }
}
