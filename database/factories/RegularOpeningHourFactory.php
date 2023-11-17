<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RegularOpeningHourFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'frequency' => \App\Models\RegularOpeningHour::FREQUENCY_WEEKLY,
            'weekday' => \App\Models\RegularOpeningHour::WEEKDAY_MONDAY,
            'opens_at' => '09:00:00',
            'closes_at' => '17:30:00',
        ];
    }
}
