<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class InOrder implements ValidationRule
{
    /**
     * @var array
     */
    protected $orders;

    /**
     * Create a new rule instance.
     */
    public function __construct(array $orders)
    {
        $this->orders = $orders;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     * @param mixed $fail
     */
    public function validate(string $attribute, $value, $fail): void
    {
        // Immediately fail if the value is not a integer.
        if (!is_int($value)) {
            $fail(__('validation.integer'));
        }

        // Initialise count to 0.
        $count = 0;

        // Loop through each order and increment count if the current value is in there.
        foreach ($this->orders as $order) {
            if ($order === $value) {
                $count++;
            }
        }

        // Loop through each order and check if in order.
        foreach (range(1, count($this->orders)) as $index) {
            if (!in_array($index, $this->orders)) {
                $fail($this->message());
            }
        }

        // Pass if the count is not more than 1.
        if ($count > 1) {
            $fail('The :value occurs more than once in the order');
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute is not in a valid order.';
    }
}
