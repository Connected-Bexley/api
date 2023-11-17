<?php

namespace App\UpdateRequest;

use App\Contracts\AppliesUpdateRequests;
use App\Generators\UniqueSlugGenerator;
use App\Http\Requests\Page\StoreRequest;
use App\Models\File;
use App\Models\Page;
use App\Models\UpdateRequest;
use App\Rules\FileIsMimeType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class NewPage implements AppliesUpdateRequests
{
    /**
     * Unique Slug Generator.
     *
     * @var \App\Generators\UniqueSlugGenerator
     */
    protected $slugGenerator;

    public function __construct(UniqueSlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

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
            'sometimes',
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
        $data = collect($updateRequest->data);

        $page = Page::make([
            'title' => $data->get('title'),
            'slug' => $this->slugGenerator->generate($data->get('slug', $data->get('title')), table(Page::class)),
            'excerpt' => $data->get('excerpt'),
            'page_type' => $data->get('page_type', Page::PAGE_TYPE_INFORMATION),
            'content' => $data->get('content', []),
        ]);

        // Update parent relationship
        if ($data->has('parent_id')) {
            $page->updateParent($data['parent_id']);
        }

        // Update status
        if ($data->has('status')) {
            $page->updateStatus($data['enabled']);
        }

        // Update order
        if ($data->has('order')) {
            $page->updateOrder($data['order']);
        }

        // Update image
        if ($data->has('image_file_id')) {
            $page->updateImage($data['image_file_id']);
        }

        // Update model so far
        $page->save();

        // Update collections relationships
        if ($data->has('collections')) {
            $page->updateCollections($data['collections']);
        }

        return $updateRequest;
    }

    /**
     * Custom logic for returning the data. Useful when wanting to transform
     * or modify the data before returning it, e.g. removing passwords.
     */
    public function getData(array $data): array
    {
        return $data;
    }
}
