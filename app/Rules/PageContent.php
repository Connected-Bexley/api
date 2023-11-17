<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PageContent implements ValidationRule
{
    /**
     * The error message to return.
     *
     * @var string
     */
    protected $message;

    /**
     * The page type: landing, information, etc.
     *
     * @var string
     */
    protected $pageType;

    /**
     * @param mixed $pageType
     */
    public function __construct($pageType = 'information')
    {
        $this->pageType = $pageType;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Immediately fail if the value is not an array.
        if (!is_array($value)) {
            $fail(__('validation.array'));
        }

        if ($value['type'] === 'copy') {
            if (!array_key_exists('value', $value)) {
                $fail('Invalid format for content');
            }

            if (($this->pageType === 'landing' && mb_strpos($attribute, 'introduction') && empty($value['value']))) {
                $fail('Page content is required for introduction');
            }

        }
        if ($value['type'] === 'cta') {
            if (empty($value['title']) || !is_string($value['title'])) {
                $fail('Call to action title is required');
            }

            if (empty($value['description']) || !is_string($value['description'])) {
                $fail('Call to action description is required');
            }

            if ((!empty($value['url']) && empty($value['buttonText'])) || (empty($value['url']) && !empty($value['buttonText']))) {
                $fail('Call to action with a link requires both the URL and the button text');
            }

            if (!empty($value['url']) && filter_var($value['url'], FILTER_VALIDATE_URL) === false) {
                $fail('Call to action link must be a valid URL');
            }

        }
    }
}
