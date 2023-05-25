<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayOpeningHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'is_closed' => true,
            'starts_at' => '2018-12-23',
            'ends_at' => '2019-01-01',
            'opens_at' => '00:00:00',
            'closes_at' => '00:00:00',
        ];
    }
}
