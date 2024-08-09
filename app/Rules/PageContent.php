<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

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

            return;
        }

        if ($value['type'] === 'copy') {
            if (!array_key_exists('value', $value)) {
                $fail('Invalid format for content');

                return;
            }

            if (($this->pageType === 'landing' && mb_strpos($attribute, 'introduction') && empty($value['value']))) {
                $fail('Page content is required for introduction');
            } elseif (!empty($value['value'])) {
                $validateMarkdown = new MarkdownMaxLength(config('local.page_copy_max_chars'), 'Description tab - The page content must be ' . config('local.page_copy_max_chars') . ' characters or fewer.');

                $validateMarkdown->validate($attribute, $value['value'], fn ($msg) => $fail($msg));
            }

        } elseif ($value['type'] === 'cta') {
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
        } elseif ($value['type'] === 'video') {
            if (empty($value['title']) || !is_string($value['title'])) {
                $fail('Video title is required');
            }

            if (empty($value['url']) || !is_string($value['url'])) {
                $fail('Video url is required');
            }

            if (!empty($value['url'])) {
                if (filter_var($value['url'], FILTER_VALIDATE_URL) === false) {
                    $fail('Video url must be a valid URL');
                }

                if (!Str::startsWith($value['url'], [
                    'https://www.youtube.com',
                    'https://player.vimeo.com',
                    'https://vimeo.com',
                ])) {
                    $fail('The video url must be provided by either YouTube or Vimeo.');
                }
            }
        } else {
            $fail('Invalid content type');
        }
    }
}
