<?php

namespace App\Rules;

class NullableIf
{
    /**
     * The condition that validates the attribute.
     *
     * @var callable|bool
     */
    public $condition;

    /**
     * The rule that will be used if the condition fails.
     *
     * @var string
     */
    protected $nonNullableRule;

    /**
     * Create a new nullable validation rule based on a condition.
     *
     * @param callable|bool $condition
     * @param mixed $nonNullableRule
     */
    public function __construct($condition, $nonNullableRule = '')
    {
        $this->condition = $condition;
        $this->nonNullableRule = $nonNullableRule;
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        if (is_callable($this->condition)) {
            return call_user_func($this->condition) ? 'nullable' : $this->nonNullableRule;
        }

        return $this->condition ? 'nullable' : $this->nonNullableRule;
    }
}
