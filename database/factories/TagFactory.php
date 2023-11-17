<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $label = $this->faker->unique()->words(2, true);

        return [
            'slug' => Str::slug($label),
            'label' => $label,
        ];
    }
}
