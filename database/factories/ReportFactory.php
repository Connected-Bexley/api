<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'report_type_id' => \App\Models\ReportType::usersExport()->id,
            'file_id' => function () {
                return \App\Models\File::factory()->create()->id;
            },
        ];
    }
}
