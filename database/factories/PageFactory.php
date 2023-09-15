<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\File;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => [
                'introduction' => [
                    'content' => [
                        [
                            'type' => 'copy',
                            'value' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                        ],
                    ],
                ],
            ],
            'enabled' => Page::ENABLED,
            'page_type' => Page::PAGE_TYPE_INFORMATION,
        ];
    }

    public function withImage()
    {
        return $this->state(function () {
            return [
                'image_file_id' => File::factory()->create(
                    ['filename' => Str::random() . '.png', 'mime_type' => 'image/png']
                ),
            ];
        });
    }

    public function disabled()
    {
        return $this->state(function () {
            return ['enabled' => Page::DISABLED];
        });
    }

    public function landingPage()
    {
        return $this->state(function () {
            return [
                'page_type' => Page::PAGE_TYPE_LANDING,
                'content' => [
                    'introduction' => [
                        'content' => [
                            [
                                'type' => 'copy',
                                'value' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                            ],
                            [
                                'type' => 'cta',
                                'title' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
                                'description' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                                'url' => $this->faker->url(),
                                'buttonText' => $this->faker->words(3, true),
                            ],
                        ],
                    ],
                    'about' => [
                        'content' => [
                            [
                                'type' => 'copy',
                                'value' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                            ],
                            [
                                'type' => 'cta',
                                'title' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
                                'description' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                                'url' => $this->faker->url(),
                                'buttonText' => $this->faker->words(3, true),
                            ],
                            [
                                'type' => 'copy',
                                'value' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                            ],
                        ],
                    ],
                    'info-pages' => [
                        'title' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
                        'content' => [
                            [
                                'type' => 'copy',
                                'value' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.']]],
                    'collections' => [
                        'title' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
                        'content' => [
                            [
                                'type' => 'copy',
                                'value' => 'Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.',
                            ],
                        ],
                    ],
                ],
            ];
        });
    }

    public function withCollections()
    {
        return $this->afterCreating(function (Page $page) {
            $page->collections()->attach(Collection::factory()->count(3)->create()->pluck('id')->all());
            $page->save();
        })->state([]);
    }

    public function withChildren()
    {
        return $this->afterCreating(function (Page $page) {
            Page::factory()->count(3)->create()->each(function (Page $child) use ($page) {
                $page->appendNode($child);
            });
        })->state([]);
    }

    public function withParent()
    {
        return $this->afterCreating(function (Page $page) {
            Page::factory()->create()->appendNode($page);
        })->state([]);
    }
}
