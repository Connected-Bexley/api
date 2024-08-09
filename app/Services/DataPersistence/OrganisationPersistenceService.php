<?php

namespace App\Services\DataPersistence;

use App\Contracts\DataPersistenceService;
use App\Models\Model;
use App\Models\Organisation;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class OrganisationPersistenceService implements DataPersistenceService
{
    use ResizesImages;
    use HasUniqueSlug;

    /**
     * Store the model.
     *
     * @return UpdateRequest|Organisation
     */
    public function store(FormRequest $request)
    {
        return $request->user()->isSuperAdmin()
        ? $this->processAsNewEntity($request)
        : $this->processAsUpdateRequest($request, null);
    }

    /**
     * Update the model.
     */
    public function update(FormRequest $request, Model $model): UpdateRequest
    {
        return $this->processAsUpdateRequest($request, $model);
    }

    /**
     * Create a new model from the provided request.
     */
    public function processAsNewEntity(FormRequest $request): Organisation
    {
        return DB::transaction(function () use ($request) {
            // Create the Organisation.
            $organisation = Organisation::create([
                'slug' => $this->uniqueSlug($request->input('slug', $request->input('name')), (new Organisation())),
                'name' => $request->name,
                'description' => sanitize_markdown($request->description),
                'url' => $request->url,
                'email' => $request->email,
                'phone' => $request->phone,
                'logo_file_id' => $request->logo_file_id,
            ]);

            if ($request->filled('logo_file_id')) {
                $this->resizeImageFile($request->logo_file_id);
            }

            // Create the social media records.
            if ($request->filled('social_medias')) {
                foreach ($request->social_medias as $socialMedia) {
                    $organisation->socialMedias()->create([
                        'type' => $socialMedia['type'],
                        'url' => $socialMedia['url'],
                    ]);
                }
            }

            // Create the category taxonomy records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $organisation->syncTaxonomyRelationships($taxonomies);

            return $organisation;
        });
    }

    /**
     * Process the requested changes and either update the model or store an update request.
     */
    public function processAsUpdateRequest(FormRequest $request, ?Organisation $organisation): UpdateRequest
    {
        return DB::transaction(function () use ($request, $organisation) {
            $data = array_filter_missing([
                'slug' => $request->missingValue('slug'),
                'name' => $request->missingValue('name'),
                'description' => $request->missingValue('description', function ($description) {
                    return sanitize_markdown($description);
                }),
                'url' => $request->missingValue('url'),
                'email' => $request->missingValue('email'),
                'phone' => $request->missingValue('phone'),
                'logo_file_id' => $request->missingValue('logo_file_id'),
                'category_taxonomies' => $request->missingValue('category_taxonomies'),
                'social_medias' => $request->missingValue('social_medias'),
            ]);

            $updateableType = UpdateRequest::EXISTING_TYPE_ORGANISATION;
            if (!$organisation) {
                $updateableType = UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN;
            }

            $updateRequest = new UpdateRequest([
                'updateable_type' => $updateableType,
                'updateable_id' => $organisation ? $organisation->id : null,
                'user_id' => $request->user()->id,
                'data' => $data,
            ]);

            // Only persist to the database if the user did not request a preview.
            if ($updateRequest->updateable_type === UpdateRequest::EXISTING_TYPE_ORGANISATION) {
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
}
