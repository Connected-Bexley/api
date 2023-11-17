<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\SocialMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganisationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->company() . ' ' . $this->faker->word() . ' ' . mt_rand(1, 100000);

        return [
            'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
            'name' => $name,
            'description' => 'This organisation provides x service.',
            'url' => $this->faker->url(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_phone(),
        ];
    }

    public function socialMedia()
    {
        return $this->afterCreating(function ($organisation) {
            $organisation->socialMedias()->create([
                'type' => SocialMedia::TYPE_TWITTER,
                'url' => "https://twitter.com/{$this->faker->domainWord()}/",
            ]);
        })->state([]);
    }

    public function withJpgLogo()
    {
        return $this->state(function () {
            return [
                'logo_file_id' => File::factory()->imageJpg()->create(),
            ];
        });
    }

    public function withPngLogo()
    {
        return $this->state(function () {
            return [
                'logo_file_id' => File::factory()->imagePng()->create(),
            ];
        });
    }
}
