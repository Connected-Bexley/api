<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRefreshTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'service_id' => function () {
                return \App\Models\Service::factory()->create()->id;
            },
        ];
    }
}
