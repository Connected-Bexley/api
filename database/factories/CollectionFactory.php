<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $imageId = File::factory()->imageSvg()->create()->id;
        $name = $this->faker->sentence(2);

        return [
            'type' => Collection::TYPE_CATEGORY,
            'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
            'name' => $name,
            'meta' => [
                'intro' => $this->faker->sentence(),
                'sideboxes' => [],
                'image_file_id' => $imageId,
            ],
            'order' => Collection::categories()->count() + 1,
            'enabled' => true,
        ];
    }

    public function typePersona()
    {
        return $this->state(function () {
            return [
                'type' => Collection::TYPE_PERSONA,
                'meta' => [
                    'intro' => $this->faker->sentence(),
                    'subtitle' => $this->faker->sentence(),
                    'sideboxes' => [],
                ],
                'order' => Collection::personas()->count() + 1,
            ];
        });
    }

    public function typeOrganisationEvent()
    {
        return $this->state(function () {
            return [
                'type' => Collection::TYPE_ORGANISATION_EVENT,
                'order' => Collection::organisationEvents()->count() + 1,
            ];
        });
    }
}
