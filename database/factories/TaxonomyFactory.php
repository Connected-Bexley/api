<?php

namespace Database\Factories;

use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaxonomyFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
            'name' => $name,
            'parent_id' => Taxonomy::category()->children()->first()->id,
            'order' => 0,
            'depth' => 2,
        ];
    }

    public function lgaStandards()
    {
        return $this->state(function () {
            return ['parent_id' => Taxonomy::category()->children()->where('name', 'LGA Standards')->value('id')];
        });
    }

    public function openActive()
    {
        return $this->state(function () {
            return ['parent_id' => Taxonomy::category()->children()->where('name', 'OpenActive')->value('id')];
        });
    }
}
