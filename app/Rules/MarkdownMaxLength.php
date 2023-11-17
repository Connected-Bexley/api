<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Parsedown;

class MarkdownMaxLength implements ValidationRule
{
    /**
     * @var int
     */
    protected $maxLength;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * MarkdownMaxLength constructor.
     */
    public function __construct(int $maxLength, string $message = null)
    {
        $this->maxLength = $maxLength;
        $this->message = $message;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $html = (new Parsedown())->text(sanitize_markdown($value));
        $text = strip_tags($html);

        if (mb_strlen($text) > $this->maxLength) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return $this->message ?? "The :attribute must be not more than {$this->maxLength} characters long.";
    }
}
