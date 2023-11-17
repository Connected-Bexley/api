<?php

namespace App\Models;

use App\Contracts\AppliesUpdateRequests;
use App\Http\Requests\Organisation\UpdateRequest as UpdateOrganisationRequest;
use App\Models\Mutators\OrganisationMutators;
use App\Models\Relationships\OrganisationRelationships;
use App\Models\Scopes\OrganisationScopes;
use App\Rules\FileIsMimeType;
use App\TaxonomyRelationships\HasTaxonomyRelationships;
use App\TaxonomyRelationships\UpdateTaxonomyRelationships;
use App\UpdateRequest\UpdateRequests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class Organisation extends Model implements AppliesUpdateRequests, HasTaxonomyRelationships
{
    use HasFactory;
    use OrganisationMutators;
    use OrganisationRelationships;
    use OrganisationScopes;
    use UpdateRequests;
    use UpdateTaxonomyRelationships;

    /**
     * Return the OrganisationTaxonomy relationship.
     */
    public function taxonomyRelationship(): HasMany
    {
        return $this->organisationTaxonomies();
    }

    /**
     * Check if the update request is valid.
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $rules = (new UpdateOrganisationRequest())
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->merge(['organisation' => $this])
            ->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['logo_file_id'] = [
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG, File::MIME_TYPE_SVG),
        ];

        return ValidatorFacade::make($updateRequest->data, $rules);
    }

    /**
     * Apply the update request.
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        $data = $updateRequest->data;

        $this->update([
            'slug' => Arr::get($data, 'slug', $this->slug),
            'name' => Arr::get($data, 'name', $this->name),
            'description' => sanitize_markdown(Arr::get($data, 'description', $this->description)),
            'url' => Arr::get($data, 'url', $this->url),
            'email' => Arr::get($data, 'email', $this->email),
            'phone' => Arr::get($data, 'phone', $this->phone),
            'logo_file_id' => Arr::get($data, 'logo_file_id', $this->logo_file_id),
        ]);

        // Update the social media records.
        if (array_key_exists('social_medias', $updateRequest->data)) {
            $this->socialMedias()->delete();
            foreach ($data['social_medias'] as $socialMedia) {
                $this->socialMedias()->create([
                    'type' => $socialMedia['type'],
                    'url' => $socialMedia['url'],
                ]);
            }
        }

        // Update the category taxonomy records.
        if (array_key_exists('category_taxonomies', $data)) {
            $taxonomies = Taxonomy::whereIn('id', $data['category_taxonomies'])->get();
            $this->syncTaxonomyRelationships($taxonomies);
        }

        return $updateRequest;
    }

    /**
     * Delete polymorphic relationships when deleting.
     */
    public function delete(): ?bool
    {
        $this->socialMedias()->delete();

        return parent::delete();
    }

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     */
    public function getData(array $data): array
    {
        return $data;
    }

    public function touchServices(): Organisation
    {
        $this->services()->get()->searchable();

        return $this;
    }

    public function hasLogo(): bool
    {
        return $this->logo_file_id !== null;
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return \App\Models\File|\Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderLogo(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_ORGANISATION);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/organisation.png'),
            Response::HTTP_OK,
            ['Content-Type' => File::MIME_TYPE_PNG]
        );
    }
}
