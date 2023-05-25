<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'address_line_1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'county' => 'West Yorkshire',
            'postcode' => $this->faker->postcode(),
            'country' => 'United Kingdom',
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
            'lat' => mt_rand(-90, 90),
            'lon' => mt_rand(-180, 180),
        ];
    }
}
