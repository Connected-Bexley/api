<?php

namespace Database\Factories;

use App\Models\SocialMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganisationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
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
}
