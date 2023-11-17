<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
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

    public function withJpgImage()
    {
        return $this->state(function () {
            return [
                'image_file_id' => File::factory()->imageJpg()->create(),
            ];
        });
    }

    public function withPngImage()
    {
        return $this->state(function () {
            return [
                'image_file_id' => File::factory()->imagePng()->create(),
            ];
        });
    }
}
