<?php

namespace Database\Factories;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'service_id' => function () {
                return \App\Models\Service::factory()->create()->id;
            },
            'status' => Referral::STATUS_NEW,
            'name' => $this->faker->name(),
        ];
    }
}
