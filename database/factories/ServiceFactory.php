<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
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
            'organisation_id' => function () {
                return \App\Models\Organisation::factory()->create()->id;
            },
            'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
            'name' => $name,
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
            'description' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
            'is_free' => true,
            'url' => $this->faker->url(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail(),
            'show_referral_disclaimer' => false,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'cqc_location_id' => $this->faker->numerify('#-#########'),
            'score' => 1,
            'ends_at' => null,
            'last_modified_at' => Date::now(),
        ];
    }

    public function score()
    {
        return $this->state(function () {
            return [
                'score' => $this->faker->numberBetween(1, 5),
            ];
        });
    }

    public function withCategoryTaxonomies()
    {
        return $this->afterCreating(function (Service $service) {
            $service->syncTaxonomyRelationships(collect([Taxonomy::factory()->create()]));
        })->state([]);
    }

    public function withEligibilityTaxonomies()
    {
        return $this->afterCreating(function (Service $service) {
            // Loop through each top level child of service eligibility taxonomy
            Taxonomy::serviceEligibility()->children->each((function ($topLevelChild) use ($service) {
                // And for each top level child, attach one of its children to the service
                $service->serviceEligibilities()->create([
                    'taxonomy_id' => $topLevelChild->children->first()->id,
                ]);
            }));
        })->state([]);
    }

    public function withCustomEligibilities()
    {
        return $this->afterCreating(function (Service $service) {
            $service->eligibility_age_group_custom = 'custom age group';
            $service->eligibility_disability_custom = 'custom disability';
            $service->eligibility_gender_custom = 'custom gender';
            $service->eligibility_income_custom = 'custom income';
            $service->eligibility_language_custom = 'custom language';
            $service->eligibility_ethnicity_custom = 'custom ethnicity';
            $service->eligibility_housing_custom = 'custom housing';
            $service->eligibility_other_custom = 'custom other';
            $service->save();
        })->state([]);
    }

    public function withSocialMedia()
    {
        return $this->afterCreating(function (Service $service) {
            $service->socialMedias()->create([
                'type' => SocialMedia::TYPE_INSTAGRAM,
                'url' => 'https://www.instagram.com/ayupdigital/',
            ]);
        })->state([]);
    }

    public function withUsefulInfo()
    {
        return $this->afterCreating(function (Service $service) {
            $service->usefulInfos()->create([
                'title' => 'Did You Know?',
                'description' => 'This is a test description',
                'order' => 1,
            ]);
        })->state([]);
    }

    public function withOfferings()
    {
        return $this->afterCreating(function (Service $service) {
            $service->offerings()->create([
                'offering' => 'Weekly club',
                'order' => 1,
            ]);
        })->state([]);
    }
}
