<?php

namespace App\Rules;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;

class FileIsMimeType implements ValidationRule
{
    /**
     * @var array
     */
    protected $mimeTypes;

    /**
     * FileIsMimeType constructor.
     */
    public function __construct(string ...$mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $fileId
     * @param mixed $fail
     */
    public function validate(string $attribute, $fileId, $fail): void
    {
        if (!in_array(
            File::findOrFail($fileId)->mime_type,
            $this->mimeTypes
        )
        ) {
            $fail($this->message());
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        $mimeTypes = implode(', ', $this->mimeTypes);

        return "The :attribute must be of type $mimeTypes.";
    }
}
