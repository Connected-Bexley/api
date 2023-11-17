<?php

namespace App\UpdateRequest;

use App\Contracts\AppliesUpdateRequests;
use App\Http\Requests\OrganisationEvent\StoreRequest;
use App\Models\File;
use App\Models\OrganisationEvent;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use App\Rules\FileIsMimeType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class NewOrganisationEventCreatedByOrgAdmin implements AppliesUpdateRequests
{
    /**
     * Check if the update request is valid.
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $rules = (new StoreRequest())
            ->merge($updateRequest->data)
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['image_file_id'] = [
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_SVG),
        ];

        return ValidatorFacade::make($updateRequest->data, $rules);
    }

    /**
     * Apply the update request.
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        $data = collect($updateRequest->data);

        $organisationEvent = OrganisationEvent::create([
            'title' => $data->get('title'),
            'start_date' => $data->get('start_date'),
            'end_date' => $data->get('end_date'),
            'start_time' => $data->get('start_time'),
            'end_time' => $data->get('end_time'),
            'intro' => $data->get('intro'),
            'description' => sanitize_markdown(
                $data->get('description')
            ),
            'is_free' => $data->get('is_free'),
            'fees_text' => $data->get('fees_text'),
            'fees_url' => $data->get('fees_url'),
            'organiser_name' => $data->get('organiser_name'),
            'organiser_phone' => $data->get('organiser_phone'),
            'organiser_email' => $data->get('organiser_email'),
            'organiser_url' => $data->get('organiser_url'),
            'booking_title' => $data->get('booking_title'),
            'booking_summary' => $data->get('booking_summary'),
            'booking_url' => $data->get('booking_url'),
            'booking_cta' => $data->get('booking_cta'),
            'homepage' => $data->get('homepage'),
            'is_virtual' => $data->get('is_virtual'),
            'organisation_id' => $data->get('organisation_id'),
            'location_id' => $data->get('location_id'),
            'image_file_id' => $data->get('image_file_id'),
        ]);

        if ($data->has('image_file_id') && !empty($data->get('image_file_id'))) {
            /** @var \App\Models\File $file */
            $file = File::findOrFail($data->get('image_file_id'))->assigned();

            // Create resized version for common dimensions.
            foreach (config('local.cached_image_dimensions') as $maxDimension) {
                $file->resizedVersion($maxDimension);
            }
        }

        if ($data->has('category_taxonomies') && !empty($data->get('category_taxonomies'))) {
            // Create the category taxonomy records.
            $taxonomies = Taxonomy::whereIn('id', $data->get('category_taxonomies'))->get();
            $organisationEvent->syncTaxonomyRelationships($taxonomies);
        }

        // Ensure conditional fields are reset if needed.
        $organisationEvent->resetConditionalFields();

        return $updateRequest;
    }

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     */
    public function getData(array $data): array
    {
        Arr::forget($data, ['user.password']);

        return $data;
    }
}
