<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Parsedown;

class MarkdownMinLength implements ValidationRule
{
    /**
     * @var int
     */
    protected $minLength;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * MarkdownMaxLength constructor.
     */
    public function __construct(int $minLength, string $message = null)
    {
        $this->minLength = $minLength;
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

        if (mb_strlen($text) < $this->minLength) {
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
        return $this->message ?? "The :attribute must be at least {$this->minLength} characters long.";
    }
}
