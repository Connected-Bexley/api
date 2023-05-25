<?php

namespace App\Models;

use App\Emails\Email;
use App\Http\Requests\Service\UpdateRequest as UpdateServiceRequest;
use App\Models\Mutators\ServiceMutators;
use App\Models\Relationships\ServiceRelationships;
use App\Models\Scopes\ServiceScopes;
use App\Notifications\Notifiable;
use App\Notifications\Notifications;
use App\Rules\FileIsMimeType;
use App\Sms\Sms;
use App\TaxonomyRelationships\HasTaxonomyRelationships;
use App\TaxonomyRelationships\UpdateServiceEligibilityTaxonomyRelationships;
use App\TaxonomyRelationships\UpdateTaxonomyRelationships;
use App\UpdateRequest\AppliesUpdateRequests;
use App\UpdateRequest\UpdateRequests;
use Carbon\CarbonImmutable;
use ElasticScoutDriverPlus\Searchable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;

class Service extends Model implements AppliesUpdateRequests, Notifiable, HasTaxonomyRelationships
{
    use HasFactory;
    use DispatchesJobs;
    use Notifications;
    use Searchable;
    use ServiceMutators;
    use ServiceRelationships;
    use ServiceScopes;
    use UpdateRequests;
    use UpdateTaxonomyRelationships;
    use UpdateServiceEligibilityTaxonomyRelationships;

    const TYPE_SERVICE = 'service';

    const TYPE_ACTIVITY = 'activity';

    const TYPE_CLUB = 'club';

    const TYPE_GROUP = 'group';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const WAIT_TIME_ONE_WEEK = 'one_week';

    const WAIT_TIME_TWO_WEEKS = 'two_weeks';

    const WAIT_TIME_THREE_WEEKS = 'three_weeks';

    const WAIT_TIME_MONTH = 'month';

    const WAIT_TIME_LONGER = 'longer';

    const REFERRAL_METHOD_INTERNAL = 'internal';

    const REFERRAL_METHOD_EXTERNAL = 'external';

    const REFERRAL_METHOD_NONE = 'none';

    const SCORE_POOR = 1;

    const SCORE_BELOW_AVERAGE = 2;

    const SCORE_AVERAGE = 3;

    const SCORE_ABOVE_AVERAGE = 4;

    const SCORE_EXCELLENT = 5;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_free' => 'boolean',
        'show_referral_disclaimer' => 'boolean',
        'ends_at' => 'datetime',
        'last_modified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $serviceEligibilities = $this->eligibilities;
        $serviceEligibilityIds = $serviceEligibilities->pluck('id')->toArray();
        $serviceEligibilityNames = $serviceEligibilities->pluck('name')->toArray();
        $serviceEligibilityRoot = Taxonomy::serviceEligibility();
        foreach ($serviceEligibilityRoot->children as $serviceEligibilityType) {
            if (!$serviceEligibilityType->filterDescendants($serviceEligibilityIds)) {
                $serviceEligibilityNames[] = $serviceEligibilityType->name . ' All';
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->onlyAlphaNumeric($this->name),
            'intro' => $this->onlyAlphaNumeric($this->intro),
            'description' => $this->onlyAlphaNumeric($this->description),
            'wait_time' => $this->wait_time,
            'is_free' => $this->is_free,
            'status' => $this->status,
            'score' => $this->score,
            'organisation_name' => $this->onlyAlphaNumeric($this->organisation->name),
            'taxonomy_categories' => $this->taxonomies()->pluck('name')->toArray(),
            'collection_categories' => static::collections($this)->where('type', Collection::TYPE_CATEGORY)->pluck('name')->toArray(),
            'collection_personas' => static::collections($this)->where('type', Collection::TYPE_PERSONA)->pluck('name')->toArray(),
            'service_locations' => $this->serviceLocations()
                ->with('location')
                ->get()
                ->map(function (ServiceLocation $serviceLocation) {
                    return [
                        'id' => $serviceLocation->id,
                        'location' => [
                            'lat' => $serviceLocation->location->lat,
                            'lon' => $serviceLocation->location->lon,
                        ],
                    ];
                })->toArray(),
            'service_eligibilities' => $serviceEligibilityNames,
        ];
    }

    /**
     * Return the ServiceTaxonomy relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxonomyRelationship(): HasMany
    {
        return $this->serviceTaxonomies();
    }

    /**
     * Check if the update request is valid.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $rules = (new UpdateServiceRequest())
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->merge(['service' => $this])
            ->merge($updateRequest->data)
            ->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['gallery_items.*.file_id'] = [
            'required_with:gallery_items.*',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG),
        ];
        $rules['logo_file_id'] = [
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG),
        ];

        return ValidatorFacade::make($updateRequest->data, $rules);
    }

    /**
     * Apply the update request.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \App\Models\UpdateRequest
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        $data = $updateRequest->data;

        // Update the Logo File entity if new
        if (Arr::get($data, 'logo_file_id', $this->logo_file_id) !== $this->logo_file_id && !empty($data['logo_file_id'])) {
            /** @var \App\Models\File $file */
            $file = File::findOrFail($data['logo_file_id'])->assigned();

            // Create resized version for common dimensions.
            foreach (config('local.cached_image_dimensions') as $maxDimension) {
                $file->resizedVersion($maxDimension);
            }
        }

        // Update the service record.
        $this->update([
            'organisation_id' => Arr::get($data, 'organisation_id', $this->organisation_id),
            'slug' => Arr::get($data, 'slug', $this->slug),
            'name' => Arr::get($data, 'name', $this->name),
            'type' => Arr::get($data, 'type', $this->type),
            'status' => Arr::get($data, 'status', $this->status),
            'intro' => Arr::get($data, 'intro', $this->intro),
            'description' => sanitize_markdown(
                Arr::get($data, 'description', $this->description)
            ),
            'wait_time' => Arr::get($data, 'wait_time', $this->wait_time),
            'is_free' => Arr::get($data, 'is_free', $this->is_free),
            'fees_text' => Arr::get($data, 'fees_text', $this->fees_text),
            'fees_url' => Arr::get($data, 'fees_url', $this->fees_url),
            'testimonial' => Arr::get($data, 'testimonial', $this->testimonial),
            'video_embed' => Arr::get($data, 'video_embed', $this->video_embed),
            'url' => Arr::get($data, 'url', $this->url),
            'contact_name' => Arr::get($data, 'contact_name', $this->contact_name),
            'contact_phone' => Arr::get($data, 'contact_phone', $this->contact_phone),
            'contact_email' => Arr::get($data, 'contact_email', $this->contact_email),
            'cqc_location_id' => config('flags.cqc_location') ? Arr::get($data, 'cqc_location_id', $this->cqc_location_id) : null,
            'show_referral_disclaimer' => Arr::get($data, 'show_referral_disclaimer', $this->show_referral_disclaimer),
            'referral_method' => Arr::get($data, 'referral_method', $this->referral_method),
            'referral_button_text' => Arr::get($data, 'referral_button_text', $this->referral_button_text),
            'referral_email' => Arr::get($data, 'referral_email', $this->referral_email),
            'referral_url' => Arr::get($data, 'referral_url', $this->referral_url),
            'logo_file_id' => Arr::get($data, 'logo_file_id', $this->logo_file_id),
            'score' => Arr::get($data, 'score', $this->score),
            'ends_at' => array_key_exists('ends_at', $data)
                ? (
                    $data['ends_at'] === null
                        ? null
                        : Date::createFromFormat(CarbonImmutable::ISO8601, $data['ends_at'])
                )
                : $this->ends_at,
            // This must always be updated regardless of the fields changed.
            'last_modified_at' => Date::now(),
            'eligibility_age_group_custom' => Arr::get($data, 'eligibility_types.custom.age_group', $this->eligibility_age_group_custom),
            'eligibility_disability_custom' => Arr::get($data, 'eligibility_types.custom.disability', $this->eligibility_disability_custom),
            'eligibility_employment_custom' => Arr::get($data, 'eligibility_types.custom.employment', $this->eligibility_employment_custom),
            'eligibility_gender_custom' => Arr::get($data, 'eligibility_types.custom.gender', $this->eligibility_gender_custom),
            'eligibility_housing_custom' => Arr::get($data, 'eligibility_types.custom.housing', $this->eligibility_housing_custom),
            'eligibility_income_custom' => Arr::get($data, 'eligibility_types.custom.income', $this->eligibility_income_custom),
            'eligibility_language_custom' => Arr::get($data, 'eligibility_types.custom.language', $this->eligibility_language_custom),
            'eligibility_ethnicity_custom' => Arr::get($data, 'eligibility_types.custom.ethnicity', $this->eligibility_ethnicity_custom),
            'eligibility_other_custom' => Arr::get($data, 'eligibility_types.custom.other', $this->eligibility_other_custom),
        ]);

        // Update the useful info records.
        if (array_key_exists('useful_infos', $data)) {
            $this->usefulInfos()->delete();
            foreach ($data['useful_infos'] as $usefulInfo) {
                $this->usefulInfos()->create([
                    'title' => $usefulInfo['title'],
                    'description' => sanitize_markdown($usefulInfo['description']),
                    'order' => $usefulInfo['order'],
                ]);
            }
        }

        // Update the offering records.
        if (array_key_exists('offerings', $data)) {
            $this->offerings()->delete();
            foreach ($data['offerings'] as $offering) {
                $this->offerings()->create([
                    'offering' => $offering['offering'],
                    'order' => $offering['order'],
                ]);
            }
        }

        // Update the tag records.
        if (array_key_exists('tags', $data)) {
            $tagIds = [];
            foreach ($data['tags'] as $tagField) {
                $tag = Tag::where('slug', Str::slug($tagField['slug']))->first();
                if (null === $tag) {
                    $tag = new Tag([
                        'slug' => Str::slug($tagField['slug']),
                        'label' => $tagField['label'],
                    ]);
                    $tag->save();
                }
                $tagIds[] = $tag->id;
            }
            $this->tags()->sync($tagIds);
        }

        // Update the gallery item records.
        if (array_key_exists('gallery_items', $data)) {
            $this->serviceGalleryItems()->delete();
            foreach ($data['gallery_items'] as $galleryItem) {
                /** @var \App\Models\File $file */
                $file = File::findOrFail($galleryItem['file_id'])->assigned();

                // Create resized version for common dimensions.
                foreach (config('local.cached_image_dimensions') as $maxDimension) {
                    $file->resizedVersion($maxDimension);
                }
                $this->serviceGalleryItems()->create([
                    'file_id' => $galleryItem['file_id'],
                ]);
            }
        }

        // Update the category taxonomy records.
        if (array_key_exists('category_taxonomies', $data)) {
            $taxonomies = Taxonomy::whereIn('id', $data['category_taxonomies'])->get();
            $this->syncTaxonomyRelationships($taxonomies);
        }

        if (array_key_exists('eligibility_types', $data)) {
            // Update the custom eligibility fields.
            if (array_key_exists('taxonomies', $data['eligibility_types'])) {
                $eligibilityTaxonomies = Taxonomy::whereIn('id', $data['eligibility_types']['taxonomies'])->get();
                $this->syncEligibilityRelationships($eligibilityTaxonomies);
            }
        }

        // Ensure conditional fields are reset if needed.
        $this->resetConditionalFields();

        return $updateRequest;
    }

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     *
     * @param array $data
     * @return array
     */
    public function getData(array $data): array
    {
        return $data;
    }

    /**
     * Ensures conditional fields are reset to expected values.
     *
     * @return \App\Models\Service
     */
    public function resetConditionalFields(): Service
    {
        if ($this->is_free) {
            $this->update([
                'fees_text' => null,
                'fees_url' => null,
            ]);
        }

        if ($this->referral_method === static::REFERRAL_METHOD_NONE) {
            $this->update([
                'referral_button_text' => null,
                'referral_email' => null,
                'referral_url' => null,
                'show_referral_disclaimer' => false,
            ]);
        }

        if ($this->referral_method === static::REFERRAL_METHOD_INTERNAL) {
            $this->update(['referral_url' => null]);
        }

        if ($this->referral_method === static::REFERRAL_METHOD_EXTERNAL) {
            $this->update(['referral_email' => null]);
        }

        return $this;
    }

    /**
     * @param \App\Emails\Email $email
     */
    public function sendEmailToContact(Email $email)
    {
        Notification::sendEmail($email, $this);
    }

    /**
     * @param \App\Sms\Sms $sms
     */
    public function sendSmsToContact(Sms $sms)
    {
        Notification::sendSms($sms, $this);
    }

    /**
     * @param string $waitTime
     * @return bool
     */
    public static function waitTimeIsValid(string $waitTime): bool
    {
        return in_array($waitTime, [
            static::WAIT_TIME_ONE_WEEK,
            static::WAIT_TIME_TWO_WEEKS,
            static::WAIT_TIME_THREE_WEEKS,
            static::WAIT_TIME_MONTH,
            static::WAIT_TIME_LONGER,
        ]);
    }

    /**
     * @return bool
     */
    public function hasLogo(): bool
    {
        return $this->logo_file_id !== null;
    }

    /**
     * @param int|null $maxDimension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return \App\Models\File|\Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderLogo(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_SERVICE);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/service.png'),
            Response::HTTP_OK,
            ['Content-Type' => File::MIME_TYPE_PNG]
        );
    }
}
