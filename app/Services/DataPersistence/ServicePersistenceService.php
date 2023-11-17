<?php

namespace App\Services\DataPersistence;

use App\Contracts\DataPersistenceService;
use App\Models\Model;
use App\Models\Service;
use App\Models\Tag;
use App\Models\Taxonomy;
use App\Models\UpdateRequest as UpdateRequestModel;
use App\Support\MissingValue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServicePersistenceService implements DataPersistenceService
{
    use ResizesImages;

    public function store(FormRequest $request)
    {
        return $request->user()->isSuperAdmin()
        ? $this->processAsNewEntity($request)
        : $this->processAsUpdateRequest($request);
    }

    public function update(FormRequest $request, Model $model)
    {
        return $this->processAsUpdateRequest($request, $model);
    }

    private function processAsUpdateRequest(FormRequest $request, $service = null)
    {
        return DB::transaction(function () use ($request, $service) {
            // Initialise the data array
            $data = array_filter_missing([
                'organisation_id' => $request->missingValue('organisation_id'),
                'slug' => $request->missingValue('slug'),
                'name' => $request->missingValue('name'),
                'type' => $request->missingValue('type'),
                'status' => $request->missingValue('status'),
                'intro' => $request->missingValue('intro'),
                'description' => $request->missingValue('description', function ($description) {
                    return sanitize_markdown($description);
                }),
                'wait_time' => $request->missingValue('wait_time'),
                'is_free' => $request->missingValue('is_free'),
                'fees_text' => $request->missingValue('fees_text'),
                'fees_url' => $request->missingValue('fees_url'),
                'testimonial' => $request->missingValue('testimonial'),
                'video_embed' => $request->missingValue('video_embed'),
                'url' => $request->missingValue('url'),
                'contact_name' => $request->missingValue('contact_name'),
                'contact_phone' => $request->missingValue('contact_phone'),
                'contact_email' => $request->missingValue('contact_email'),
                'cqc_location_id' => config('flags.cqc_location') ? $request->missingValue('cqc_location_id') : null,
                'show_referral_disclaimer' => $request->missingValue('show_referral_disclaimer'),
                'referral_method' => $request->missingValue('referral_method'),
                'referral_button_text' => $request->missingValue('referral_button_text'),
                'referral_email' => $request->missingValue('referral_email'),
                'referral_url' => $request->missingValue('referral_url'),
                'useful_infos' => $request->has('useful_infos') ? [] : new MissingValue(),
                'offerings' => $request->has('offerings') ? [] : new MissingValue(),
                'gallery_items' => $request->has('gallery_items') ? [] : new MissingValue(),
                'tags' => $request->has('tags') ? [] : new MissingValue(),
                'category_taxonomies' => $request->missingValue('category_taxonomies'),
                'eligibility_types' => $request->filled('eligibility_types') ? $request->eligibility_types : new MissingValue(),
                'logo_file_id' => $request->missingValue('logo_file_id'),
                'score' => $request->missingValue('score'),
                'ends_at' => $request->missingValue('ends_at'),
            ]);

            // Loop through each useful info.
            foreach ($request->input('useful_infos', []) as $usefulInfo) {
                $data['useful_infos'][] = [
                    'title' => $usefulInfo['title'],
                    'description' => sanitize_markdown($usefulInfo['description']),
                    'order' => $usefulInfo['order'],
                ];
            }

            // Loop through each offering.
            foreach ($request->input('offerings', []) as $offering) {
                $data['offerings'][] = [
                    'offering' => $offering['offering'],
                    'order' => $offering['order'],
                ];
            }

            // Loop through each gallery item.
            foreach ($request->input('gallery_items', []) as $galleryItem) {
                $data['gallery_items'][] = [
                    'file_id' => $galleryItem['file_id'],
                ];
            }

            // Loop through each tag.
            foreach ($request->input('tags', []) as $tag) {
                $data['tags'][] = [
                    'slug' => Str::slug($tag['slug']),
                    'label' => $tag['label'],
                ];
            }

            $updateableType = UpdateRequestModel::EXISTING_TYPE_SERVICE;
            if (!$service) {
                $updateableType = $request->user()->isGlobalAdmin() ? UpdateRequestModel::NEW_TYPE_SERVICE_GLOBAL_ADMIN : UpdateRequestModel::NEW_TYPE_SERVICE_ORG_ADMIN;
            }

            $updateRequest = new UpdateRequestModel([
                'updateable_type' => $updateableType,
                'updateable_id' => $service ? $service->id : null,
                'user_id' => $request->user()->id,
                'data' => $data,
            ]);

            // Only persist to the database if the user did not request a preview.
            if ($updateRequest->updateable_type === UpdateRequestModel::EXISTING_TYPE_SERVICE) {
                // Preview currently only available for update operations
                if (!$request->isPreview()) {
                    $updateRequest->save();
                }
            } else {
                $updateRequest->save();
            }

            return $updateRequest;
        });
    }

    private function processAsNewEntity(FormRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $initialCreateData = [
                'organisation_id' => $request->organisation_id,
                'slug' => $this->uniqueSlug($request->slug),
                'name' => $request->name,
                'type' => $request->type,
                'status' => $request->status,
                'intro' => $request->intro,
                'description' => sanitize_markdown($request->description),
                'wait_time' => $request->wait_time,
                'is_free' => $request->is_free,
                'fees_text' => $request->fees_text,
                'fees_url' => $request->fees_url,
                'testimonial' => $request->testimonial,
                'video_embed' => $request->video_embed,
                'url' => $request->url,
                'contact_name' => $request->contact_name,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'cqc_location_id' => config('flags.cqc_location') ? $request->cqc_location_id : null,
                'show_referral_disclaimer' => $request->show_referral_disclaimer,
                'referral_method' => $request->referral_method,
                'referral_button_text' => $request->referral_button_text,
                'referral_email' => $request->referral_email,
                'referral_url' => $request->referral_url,
                'logo_file_id' => $request->logo_file_id,
                'score' => $request->score,
                'last_modified_at' => Date::now(),
                'ends_at' => $request->filled('ends_at')
                ? Date::createFromFormat(CarbonImmutable::ISO8601, $request->ends_at)
                : null,
            ];

            foreach ($request->input('eligibility_types.custom', []) as $customEligibilityType => $value) {
                $fieldName = 'eligibility_' . $customEligibilityType . '_custom';
                $initialCreateData[$fieldName] = $value;
            }

            // Create the service record.
            /** @var \App\Models\Service $service */
            $service = Service::create($initialCreateData);

            if ($request->filled('gallery_items')) {
                foreach ($request->gallery_items as $galleryItem) {
                    $this->resizeImageFile($galleryItem['file_id']);
                }
            }

            if ($request->filled('logo_file_id')) {
                $this->resizeImageFile($request->logo_file_id);
            }

            // Create the useful info records.
            foreach ($request->useful_infos as $usefulInfo) {
                $service->usefulInfos()->create([
                    'title' => $usefulInfo['title'],
                    'description' => sanitize_markdown($usefulInfo['description']),
                    'order' => $usefulInfo['order'],
                ]);
            }

            // Create the offering records.
            foreach ($request->offerings as $offering) {
                $service->offerings()->create([
                    'offering' => $offering['offering'],
                    'order' => $offering['order'],
                ]);
            }

            // Create the gallery item records.
            foreach ($request->gallery_items as $galleryItem) {
                $service->serviceGalleryItems()->create([
                    'file_id' => $galleryItem['file_id'],
                ]);
            }

            // Create the tag records.
            if (config('flags.service_tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagField) {
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
                $service->tags()->sync($tagIds);
            }

            // Create the category taxonomy records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $service->syncTaxonomyRelationships($taxonomies);

            // Create the service eligibility taxonomy records and custom fields
            $eligibilityTypes = Taxonomy::whereIn('id', $request->input('eligibility_types.taxonomies', []))->get();
            $service->syncEligibilityRelationships($eligibilityTypes);

            return $service;
        });
    }

    /**
     * Return a unique version of the proposed slug.
     */
    public function uniqueSlug(string $slug): string
    {
        $uniqueSlug = $baseSlug = preg_replace('|\-\d$|', '', $slug);
        $suffix = 1;
        do {
            $exists = DB::table((new Service())->getTable())->where('slug', $uniqueSlug)->exists();
            if ($exists) {
                $uniqueSlug = $baseSlug . '-' . $suffix;
            }
            $suffix++;
        } while ($exists);

        return $uniqueSlug;
    }
}
