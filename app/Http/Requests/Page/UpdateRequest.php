<?php

namespace App\Http\Requests\Page;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Page;
use App\Models\Role;
use App\Models\UserRole;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\InformationPageCannotHaveCollection;
use App\Rules\LandingPageCannotHaveParent;
use App\Rules\PageContent;
use App\Rules\Slug;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use HasMissingValues;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()->isContentAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxOrder = $this->parent_id ?
        Page::findOrFail($this->parent_id)->children->count() :
        ($this->page->parent ?
            $this->page->siblingsAndSelf()->count() :
            Page::whereIsRoot()->count());

        return [
            'title' => ['sometimes', 'string', 'min:1', 'max:255'],
            'slug' => [
                'string',
                'min:1',
                'max:255',
                new Slug(),
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::contentAdmin()->id,
                    ]),
                    $this->page->slug
                ),
            ],
            'excerpt' => ['sometimes', 'nullable', 'string', 'min:1', 'max:150'],
            'content' => ['sometimes', 'array'],
            'content.info_pages.*.title' => ['sometimes', 'string', 'min:1'],
            'content.collections.*.title' => ['sometimes', 'string', 'min:1'],
            'content.*.title' => ['sometimes', 'string'],
            'content.*.content' => ['sometimes', 'array'],
            'content.*.content.*' => [new PageContent($this->page->page_type)],
            'order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:' . $maxOrder,
            ],
            'enabled' => [
                'sometimes',
                'boolean',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::contentAdmin()->id,
                    ]),
                    $this->page->enabled
                ),
            ],
            'page_type' => [
                'sometimes',
                Rule::in([Page::PAGE_TYPE_INFORMATION, Page::PAGE_TYPE_LANDING]),
                new LandingPageCannotHaveParent($this->has('parent_id') ? $this->parent_id : $this->page->parent_uuid),
            ],
            'parent_id' => ['sometimes', 'nullable', 'string', 'exists:pages,id'],
            'image_file_id' => [
                'sometimes',
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG, File::MIME_TYPE_JPG, File::MIME_TYPE_JPEG, File::MIME_TYPE_SVG),
                new FileIsPendingAssignment(),
            ],
            'collections' => [
                'sometimes',
                'array',
                new InformationPageCannotHaveCollection($this->input('page_type', $this->page->page_type)),
            ],
            'collections.*' => ['sometimes', 'exists:collections,id'],
        ];
    }

    /**
     * Check if the user requested only a preview of the update request.
     */
    public function isPreview(): bool
    {
        return $this->preview === true;
    }
}
