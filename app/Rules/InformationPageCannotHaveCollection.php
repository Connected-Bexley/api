<?php

namespace App\Rules;

use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;

class InformationPageCannotHaveCollection implements ValidationRule
{
    /**
     * Page type.
     *
     * @var string
     */
    protected $pageType;

    /**
     * Create a new rule instance.
     *
     * @param \App\Models\Page $page
     * @param mixed $pageId
     * @param mixed $pageType
     */
    public function __construct($pageType)
    {
        $this->pageType = $pageType;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     * @param mixed $fail
     */
    public function validate(string $attribute, $value, $fail): void
    {
        if (!empty($value) && $this->pageType !== Page::PAGE_TYPE_LANDING) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Pages of type other than ' . Page::PAGE_TYPE_LANDING . ' cannot have collections';
    }
}
