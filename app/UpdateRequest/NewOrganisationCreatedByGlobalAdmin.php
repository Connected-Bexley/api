<?php

namespace App\UpdateRequest;

use App\Contracts\AppliesUpdateRequests;
use App\Http\Requests\Organisation\StoreRequest;
use App\Models\File;
use App\Models\Organisation;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use App\Rules\FileIsMimeType;
use App\Services\DataPersistence\ResizesImages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class NewOrganisationCreatedByGlobalAdmin implements AppliesUpdateRequests
{
    use ResizesImages;

    /**
     * Check if the update request is valid.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $user = Auth::user();
        $rules = (new StoreRequest())
            ->merge($updateRequest->data)
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['logo_file_id'] = [
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_SVG),
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
        $data = collect($updateRequest->data);

        $organisation = Organisation::create([
            'slug' => $data->get('slug'),
            'name' => $data->get('name'),
            'description' => sanitize_markdown(
                $data->get('description')
            ),
            'url' => $data->get('url'),
            'email' => $data->get('email'),
            'phone' => $data->get('phone'),
            'logo_file_id' => $data->get('logo_file_id'),
        ]);

        if ($data->has('logo_file_id') && !empty($data->get('logo_file_id'))) {
            $this->resizeImageFile($data->get('logo_file_id'));
        }

        // Create the social media records.
        if ($data->has('social_medias')) {
            foreach ($data['social_medias'] as $socialMedia) {
                $organisation->socialMedias()->create([
                    'type' => $socialMedia['type'],
                    'url' => $socialMedia['url'],
                ]);
            }
        }

        if ($data->has('category_taxonomies') && !empty($data->get('category_taxonomies'))) {
            // Create the category taxonomy records.
            $taxonomies = Taxonomy::whereIn('id', $data->get('category_taxonomies'))->get();
            $organisation->syncTaxonomyRelationships($taxonomies);
        }

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
        Arr::forget($data, ['user.password']);

        return $data;
    }
}
