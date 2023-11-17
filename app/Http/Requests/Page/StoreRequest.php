<?php

namespace App\Http\Requests\Page;

use App\Http\Requests\HasMissingValues;
use App\Models\File;
use App\Models\Page;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\InformationPageCannotHaveCollection;
use App\Rules\LandingPageCannotHaveParent;
use App\Rules\PageContent;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
        Page::whereIsRoot()->count();

        return [
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'slug' => ['string', 'min:1', 'max:255', new Slug()],
            'excerpt' => ['sometimes', 'nullable', 'string', 'min:1', 'max:150'],
            'content' => ['required_if:page_type,landing', 'array'],
            'content.introduction.content' => ['required_if:page_type,landing', 'array'],
            'content.info_pages.title' => [
                'required_if:page_type,landing',
                'string',
                'min:1',
            ],
            'content.collections.title' => [
                'required_if:page_type,landing',
                'string',
                'min:1',
            ],
            'content.*.title' => ['sometimes', 'string'],
            'content.*.content' => ['sometimes', 'array'],
            'content.*.content.*' => [new PageContent($this->page_type)],
            'order' => [
                'integer',
                'min:0',
                'max:' . $maxOrder,
            ],
            'page_type' => [
                'sometimes',
                Rule::in([Page::PAGE_TYPE_INFORMATION, Page::PAGE_TYPE_LANDING]),
                new LandingPageCannotHaveParent($this->input('parent_id')),
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
                new InformationPageCannotHaveCollection($this->input('page_type', Page::PAGE_TYPE_INFORMATION)),
            ],
            'collections.*' => ['sometimes', 'exists:collections,id'],
        ];
    }
}
