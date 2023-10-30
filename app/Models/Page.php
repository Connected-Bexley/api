<?php

namespace App\Models;

use App\Contracts\AppliesUpdateRequests;
use App\Generators\UniqueSlugGenerator;
use App\Http\Requests\Page\UpdateRequest as UpdatePageRequest;
use App\Models\Mutators\PageMutators;
use App\Models\Relationships\PageRelationships;
use App\Models\Scopes\PageScopes;
use App\Rules\FileIsMimeType;
use ElasticScoutDriverPlus\Searchable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Kalnoy\Nestedset\NodeTrait;

class Page extends Model implements AppliesUpdateRequests
{
    use HasFactory;
    use PageRelationships;
    use PageMutators;
    use PageScopes;
    use NodeTrait;

    /**
     * NodeTrait::usesSoftDelete and Laravel\Scout\Searchable::usesSoftDelete clash.
     */
    use Searchable {
        Searchable::usesSoftDelete insteadof NodeTrait;
    }

    const DISABLED = false;

    const ENABLED = true;

    const PARENT_KEY = 'parent_uuid';

    const PAGE_TYPE_INFORMATION = 'information';

    const PAGE_TYPE_LANDING = 'landing';

    /**
     * Attributes that need to be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'content' => 'array',
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'page_type' => self::PAGE_TYPE_INFORMATION,
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $contentSections = [];
        foreach ($this->content as $sectionLabel => $sectionContent) {
            $content = [];
            foreach ($sectionContent['content'] as $i => $contentBlock) {
                switch ($contentBlock['type']) {
                    case 'copy':
                        $content[] = $this->onlyAlphaNumeric($contentBlock['value']);
                        break;
                    case 'cta':
                        $content[] = $this->onlyAlphaNumeric($contentBlock['title'] . ' ' . $contentBlock['description']);
                        break;
                    default:
                        break;
                }
            }

            $contentSections[$sectionLabel] = [
                'title' => $sectionContent['title'] ?? '',
                'content' => implode("\n", $content),
            ];
        }

        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'title' => $this->onlyAlphaNumeric($this->title),
            'content' => $contentSections,
            'collection_categories' => $this->collections()->where('type', Collection::TYPE_CATEGORY)->pluck('name')->all(),
            'collection_personas' => $this->collections()->where('type', Collection::TYPE_PERSONA)->pluck('name')->all(),
        ];
    }

    /**
     * Enable the Page.
     *
     * @return \App\Models\Page
     */
    public function enable()
    {
        $this->enabled = static::ENABLED;

        return $this;
    }

    /**
     * Disable the Page.
     *
     * @return \App\Models\Page
     */
    public function disable()
    {
        $this->enabled = static::DISABLED;

        return $this;
    }

    /**
     * Get the parent id key name.
     *
     * @return string
     */
    public function getParentIdName()
    {
        return static::PARENT_KEY;
    }

    /**
     * Set the page_type to 'landing'.
     *
     * @return \App\Models\Page
     */
    public function asLandingPage(): self
    {
        $this->page_type = static::PAGE_TYPE_LANDING;

        return $this;
    }

    /**
     * Set the page_type to 'information'.
     *
     * @return \App\Models\Page
     */
    public function asInformationPage(): self
    {
        $this->page_type = static::PAGE_TYPE_INFORMATION;

        return $this;
    }

    /**
     * Inherit the status (if disabled) of a parent (if exists)
     * and pass on to descendants (if disabled).
     * Children do not inherit enabled status, but must be enabled individually.
     *
     * @param mixed $status
     * @return \App\Models\Page
     */
    public function updateStatus($status): self
    {
        if ($this->parent && $this->parent->enabled === self::DISABLED) {
            $this->enabled = self::DISABLED;
        } elseif (!is_null($status)) {
            $this->enabled = $status;
        }

        if ($this->enabled === self::DISABLED) {
            self::whereIn('id', $this->descendants->pluck('id'))
                ->update(['enabled' => self::DISABLED]);
        }

        $this->save();

        return $this;
    }

    /**
     * Update the parent relationship.
     *
     * @param string $parentId
     * @return \App\Models\Page
     */
    public function updateParent($parentId = false): self
    {
        // If parent_id is null save as root node
        if (is_null($parentId)) {
            $this->saveAsRoot();
        } elseif ($parentId && $parentId !== $this->parent_uuid) {
            Page::find($parentId)->appendNode($this);
        }

        return $this;
    }

    /**
     * Update the sibling order for the page.
     *
     * @param int $order
     * @return \App\Models\Page
     */
    public function updateOrder($order): self
    {
        if (!is_null($order)) {
            $siblingAtIndex = $this->siblingAtIndex($order)->first();
            $this->beforeOrAfterNode($siblingAtIndex, $siblingAtIndex->getLft() > $this->getLft());
        }

        return $this;
    }

    /**
     * Update the image relationship.
     * Can be passed either null, the current image id or a new image id.
     *
     * @param string $imageId
     * @return \App\Models\Page
     */
    public function updateImage($imageId)
    {
        if ($imageId !== $this->image_file_id) {
            $currentImage = $this->image;

            if ($imageId) {
                /** @var \App\Models\File $file */
                $file = File::findOrFail($imageId)->assigned();

                // Create resized version for common dimensions.
                foreach (config('local.cached_image_dimensions') as $maxDimension) {
                    $file->resizedVersion($maxDimension);
                }
                $this->image()->associate($file);
            } else {
                $this->image()->dissociate();
            }

            $this->save();

            if ($currentImage) {
                $currentImage->deleteFromDisk();
                $currentImage->delete();
            }
        }

        return $this;
    }

    /**
     * Update the collections relationship.
     *
     * @param array $collectionIds
     * @param mixed $collections
     * @return \App\Models\Page
     */
    public function updateCollections($collectionIds)
    {
        if (is_array($collectionIds)) {
            $this->collections()->sync($collectionIds);
            $this->save();
        }

        return $this;
    }

    /**
     * Check if the update request is valid.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $rules = (new UpdatePageRequest())
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->merge(['page' => $this])
            ->merge($updateRequest->data)
            ->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['image_file_id'] = [
            'sometimes',
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG, File::MIME_TYPE_SVG),
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
        $slugGenerator = app(UniqueSlugGenerator::class);
        $data = $updateRequest->data;
        $slug = Arr::get($data, 'slug', $this->slug);
        if ($slug !== $this->slug) {
            $slug = $slugGenerator->generate($slug, 'pages');
        }

        // Update the organisation event record.
        $this->update([
            'title' => Arr::get($data, 'title', $this->title),
            'slug' => $slug,
            'excerpt' => Arr::get($data, 'excerpt', $this->excerpt),
            'content' => Arr::get($data, 'content', $this->content),
            'page_type' => Arr::get($data, 'page_type', $this->page_type),
        ]);

        if (Arr::has($data, 'parent_id')) {
            $this->updateParent(Arr::get($data, 'parent_id'));
            $this->updateStatus(Arr::get($data, 'enabled', $this->enabled));
        }

        if (Arr::has($data, 'enabled')) {
            $this->updateStatus(Arr::get($data, 'enabled'));
        }

        if (Arr::has($data, 'order')) {
            $this->updateOrder(Arr::get($data, 'order'));
        }

        if (Arr::has($data, 'image_file_id')) {
            $this->updateImage(Arr::get($data, 'image_file_id'));
        }

        if (Arr::has($data, 'collections')) {
            $this->updateCollections(Arr::get($data, 'collections'));
        }

        // Update model so far
        $this->save();

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
}
