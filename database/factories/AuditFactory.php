<?php

namespace Database\Factories;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class AuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'action' => Arr::random([Audit::ACTION_CREATE, Audit::ACTION_READ, Audit::ACTION_UPDATE, Audit::ACTION_DELETE]),
            'description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
