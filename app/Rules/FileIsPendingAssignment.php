<?php

namespace App\Rules;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class FileIsPendingAssignment implements ValidationRule
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * FileIsPendingAssignment constructor.
     *
     * @param callable|null $callback Called if the file is not pending assignment
     */
    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $fileId
     * @param mixed $fail
     */
    public function validate(string $attribute, $fileId, $fail): void
    {
        $file = File::findOrFail($fileId);

        $passed = Arr::get($file->meta, 'type') === File::META_TYPE_PENDING_ASSIGNMENT;

        if (!$passed) {

            if ($this->callback !== null) {
                $passed = call_user_func($this->callback, $file);
            }

            if (!$passed) {
                $fail($this->message());
            }

        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute must be an unassigned file.';
    }
}
