<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganisationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('+1 week', '+6 weeks');
        $endtime = $this->faker->time('H:i:s');
        $starttime = $this->faker->time('H:i:s', $endtime);
        $title = $this->faker->unique()->words(3, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
            'intro' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit',
            'description' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. ',
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'organiser_name' => null,
            'organiser_phone' => null,
            'organiser_email' => null,
            'organiser_url' => null,
            'booking_title' => null,
            'booking_summary' => null,
            'booking_url' => null,
            'booking_cta' => null,
            'homepage' => false,
            'is_virtual' => true,
            'location_id' => null,
            'organisation_id' => function () {
                return Organisation::factory()->create()->id;
            },
            'image_file_id' => null,
        ];
    }

    public function nonFree()
    {
        return $this->state(function () {
            return [
                'is_free' => false,
                'fees_text' => $this->faker->sentence(),
                'fees_url' => $this->faker->url(),
            ];
        });
    }

    public function withOrganiser()
    {
        return $this->state(function () {
            return [
                'organiser_name' => $this->faker->name(),
                'organiser_phone' => random_uk_phone(),
                'organiser_email' => $this->faker->safeEmail(),
                'organiser_url' => $this->faker->url(),
            ];
        });
    }

    public function withBookingInfo()
    {
        return $this->state(function () {
            return [
                'booking_title' => $this->faker->sentence(3),
                'booking_summary' => $this->faker->sentence(),
                'booking_url' => $this->faker->url(),
                'booking_cta' => $this->faker->words(2, true),
            ];
        });
    }

    public function notVirtual()
    {
        return $this->state(function () {
            return [
                'is_virtual' => false,
                'location_id' => function () {
                    return Location::factory()->create()->id;
                },
            ];
        });
    }

    public function withImage()
    {
        return $this->state(function () {
            return [
                'image_file_id' => function () {
                    return File::factory()->imagePng()->create()->id;
                },
            ];
        });
    }

    public function homepage()
    {
        return $this->state(function () {
            return [
                'homepage' => true,
            ];
        });
    }
}
