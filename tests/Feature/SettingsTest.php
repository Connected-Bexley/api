<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    protected $settingsData;

    protected $settingsStructure;

    protected $settingsResponse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsData = [
            'cms' => [
                'frontend' => [
                    'global' => [
                        'footer_title' => 'data/cms/frontend/global/footer_title',
                        'footer_content' => 'data/cms/frontend/global/footer_content',
                        'contact_phone' => 'data/cms/frontend/global/contact_phone',
                        'contact_email' => 'example@example.com',
                        'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                        'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                    ],
                    'home' => [
                        'search_title' => 'data/cms/frontend/home/search_title',
                        'categories_title' => 'data/cms/frontend/home/categories_title',
                        'personas_title' => 'data/cms/frontend/home/personas_title',
                        'personas_content' => 'data/cms/frontend/home/personas_content',
                        'banners' => [
                            [
                                'title' => 'data/cms/frontend/home/banners/title',
                                'content' => 'data/cms/frontend/home/banners/content',
                                'button_text' => 'button_text',
                                'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url',
                            ],
                        ],
                    ],
                    'terms_and_conditions' => [
                        'title' => 'data/cms/frontend/terms_and_conditions/title',
                        'content' => 'data/cms/frontend/terms_and_conditions/content',
                    ],
                    'privacy_policy' => [
                        'title' => 'data/cms/frontend/privacy_policy/title',
                        'content' => 'data/cms/frontend/privacy_policy/content',
                    ],
                    'about' => [
                        'title' => 'data/cms/frontend/about/title',
                        'content' => 'data/cms/frontend/about/content',
                        'video_url' => 'https://www.youtube.com/random-video-slug',
                    ],
                    'contact' => [
                        'title' => 'data/cms/frontend/contact/title',
                        'content' => 'data/cms/frontend/contact/content',
                    ],
                    'get_involved' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'favourites' => [
                        'title' => 'data/cms/frontend/favourites/title',
                        'content' => 'data/cms/frontend/favourites/content',
                    ],
                    'banner' => [
                        'title' => 'data/cms/frontend/banner/title',
                        'content' => 'data/cms/frontend/banner/content',
                        'button_text' => 'button_text',
                        'button_url' => 'https://example.com/data/cms/frontend/banner/button_url',
                    ],
                ],
            ],
        ];

        $this->settingsStructure = [
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title',
                            'footer_content',
                            'contact_phone',
                            'contact_email',
                            'facebook_handle',
                            'twitter_handle',
                        ],
                        'home' => [
                            'search_title',
                            'categories_title',
                            'personas_title',
                            'personas_content',
                            'banners' => [
                                [
                                    'title',
                                    'content',
                                    'button_text',
                                    'button_url',
                                ],
                            ],
                        ],
                        'terms_and_conditions' => [
                            'title',
                            'content',
                        ],
                        'privacy_policy' => [
                            'title',
                            'content',
                        ],
                        'about' => [
                            'title',
                            'content',
                            'video_url',
                        ],
                        'contact' => [
                            'title',
                            'content',
                        ],
                        'get_involved' => [
                            'title',
                            'content',
                        ],
                        'favourites' => [
                            'title',
                            'content',
                        ],
                        'banner' => [
                            'title',
                            'content',
                            'button_text',
                            'button_url',
                            'has_image',
                        ],
                    ],
                ],
            ],
        ];

        $this->settingsResponse = [
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title' => 'data/cms/frontend/global/footer_title',
                            'footer_content' => 'data/cms/frontend/global/footer_content',
                            'contact_phone' => 'data/cms/frontend/global/contact_phone',
                            'contact_email' => 'example@example.com',
                            'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                            'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                        ],
                        'home' => [
                            'search_title' => 'data/cms/frontend/home/search_title',
                            'categories_title' => 'data/cms/frontend/home/categories_title',
                            'personas_title' => 'data/cms/frontend/home/personas_title',
                            'personas_content' => 'data/cms/frontend/home/personas_content',
                            'banners' => [
                                [
                                    'title' => 'data/cms/frontend/home/banners/title',
                                    'content' => 'data/cms/frontend/home/banners/content',
                                    'button_text' => 'button_text',
                                    'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url',
                                ],
                            ],
                        ],
                        'terms_and_conditions' => [
                            'title' => 'data/cms/frontend/terms_and_conditions/title',
                            'content' => 'data/cms/frontend/terms_and_conditions/content',
                        ],
                        'privacy_policy' => [
                            'title' => 'data/cms/frontend/privacy_policy/title',
                            'content' => 'data/cms/frontend/privacy_policy/content',
                        ],
                        'about' => [
                            'title' => 'data/cms/frontend/about/title',
                            'content' => 'data/cms/frontend/about/content',
                            'video_url' => 'https://www.youtube.com/random-video-slug',
                        ],
                        'contact' => [
                            'title' => 'data/cms/frontend/contact/title',
                            'content' => 'data/cms/frontend/contact/content',
                        ],
                        'get_involved' => [
                            'title' => 'data/cms/frontend/get_involved/title',
                            'content' => 'data/cms/frontend/get_involved/content',
                        ],
                        'favourites' => [
                            'title' => 'data/cms/frontend/favourites/title',
                            'content' => 'data/cms/frontend/favourites/content',
                        ],
                        'banner' => [
                            'title' => 'data/cms/frontend/banner/title',
                            'content' => 'data/cms/frontend/banner/content',
                            'button_text' => 'button_text',
                            'button_url' => 'https://example.com/data/cms/frontend/banner/button_url',
                            'has_image' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
    /*
     * List all the settings.
     */

    public function test_guest_can_list_them()
    {
        $response = $this->getJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_structure_correct_when_listed()
    {
        $response = $this->getJson('/core/v1/settings');

        $response->assertJsonStructure($this->settingsStructure);
    }

    public function test_values_correct_when_listed()
    {
        $response = $this->getJson('/core/v1/settings');

        $response->assertJson([
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title' => 'Footer title',
                            'footer_content' => 'Footer content',
                            'contact_phone' => 'Contact phone',
                            'contact_email' => 'Contact email',
                            'facebook_handle' => 'Facebook handle',
                            'twitter_handle' => 'Twitter handle',
                        ],
                        'home' => [
                            'search_title' => 'Search title',
                            'categories_title' => 'Categories title',
                            'personas_title' => 'Personas title',
                            'personas_content' => 'Personas content',
                            'banners' => [
                                [
                                    'title' => null,
                                    'content' => null,
                                    'button_text' => null,
                                    'button_url' => null,
                                ],
                            ],
                        ],
                        'terms_and_conditions' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'privacy_policy' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'about' => [
                            'title' => 'Title',
                            'content' => 'Content',
                            'video_url' => 'Video URL',
                        ],
                        'contact' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'get_involved' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'favourites' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'banner' => [
                            'title' => null,
                            'content' => null,
                            'button_text' => null,
                            'button_url' => null,
                            'has_image' => false,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->getJson('/core/v1/settings');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Update the settings.
     */

    public function test_guest_cannot_update_them()
    {
        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_them()
    {
        Passport::actingAs(
            User::factory()->create()->makeServiceWorker(
                Service::factory()->create()
            )
        );

        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_them()
    {
        Passport::actingAs(
            User::factory()->create()->makeServiceAdmin(
                Service::factory()->create()
            )
        );

        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_update_them()
    {
        Passport::actingAs(
            User::factory()->create()->makeOrganisationAdmin(
                Organisation::factory()->create()
            )
        );

        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_them()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_structure_correct_when_updated()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertJsonStructure($this->settingsStructure);
    }

    public function test_values_correct_when_updated()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertJson($this->settingsResponse);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $this->putJson('/core/v1/settings', $this->settingsData);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_UPDATE;
        });
    }

    /**
     * @test
     */
    public function video_url_is_optional()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment([
            'about' => [
                'title' => 'data/cms/frontend/about/title',
                'content' => 'data/cms/frontend/about/content',
                'video_url' => 'https://www.youtube.com/random-video-slug',
            ],
        ]);

        $this->settingsData['cms']['frontend']['about']['video_url'] = null;

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment([
            'about' => [
                'title' => 'data/cms/frontend/about/title',
                'content' => 'data/cms/frontend/about/content',
                'video_url' => null,
            ],
        ]);
    }

    /*
     * CMS / Frontend / Banner.
     */

    public function test_banner_image_can_be_update()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $image = Storage::disk('local')->get('/test-data/image.png');
        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,'.base64_encode($image),
        ]);

        $this->settingsData['cms']['frontend']['banner']['image_file_id'] = $this->getResponseContent($imageResponse, 'data.id');

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['banner']['has_image'] = true;

        $response->assertJson($this->settingsResponse);
    }

    public function test_banner_image_remains_when_not_provided()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $cmsValue = Setting::cms()->value;
        Arr::set(
            $cmsValue,
            'frontend.banner.image_file_id',
            uuid()
        );
        Setting::cms()->update(['value' => $cmsValue]);

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['banner']['has_image'] = true;

        $response->assertJson($this->settingsResponse);
    }

    public function test_banner_image_can_be_removed()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $cmsValue = Setting::cms()->value;
        Arr::set(
            $cmsValue,
            'frontend.banner.image_file_id',
            uuid()
        );
        Setting::cms()->update(['value' => $cmsValue]);

        $this->settingsData['cms']['frontend']['banner']['image_file_id'] = null;

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertJson($this->settingsResponse);
    }

    public function test_all_banner_fields_are_required()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $image = Storage::disk('local')->get('/test-data/image.png');
        $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,'.base64_encode($image),
        ]);

        $this->settingsData['cms']['frontend']['banner'] = [
            'title' => 'data/cms/frontend/banner/title',
            'content' => null,
            'button_text' => null,
            'button_url' => null,
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /*
     * Get banner image.
     */

    public function test_guest_cannot_view_banner_image_when_not_provided()
    {
        $response = $this->get('/core/v1/settings/banner-image.png');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_global_admin_can_view_banner_image()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $image = Storage::disk('local')->get('/test-data/image.png');
        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,'.base64_encode($image),
        ]);

        $cmsValue = Setting::cms()->value;
        Arr::set(
            $cmsValue,
            'frontend.banner.image_file_id',
            $this->getResponseContent($imageResponse, 'data.id')
        );
        Setting::cms()->update(['value' => $cmsValue]);

        $response = $this->get('/core/v1/settings/banner-image.png');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_banner_image_viewed()
    {
        $this->fakeEvents();

        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $image = Storage::disk('local')->get('/test-data/image.png');
        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,'.base64_encode($image),
        ]);

        $cmsValue = Setting::cms()->value;
        Arr::set(
            $cmsValue,
            'frontend.banner.image_file_id',
            $this->getResponseContent($imageResponse, 'data.id')
        );
        Setting::cms()->update(['value' => $cmsValue]);

        $this->get('/core/v1/settings/banner-image.png');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /**
     * CMS Frontend Home Banners
     */

    /**
     * @test
     */
    public function single_home_banner_can_be_added()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $this->settingsData['cms']['frontend']['home']['banners'][] = [
            'title' => 'data/cms/frontend/home/banners/title2',
            'content' => 'data/cms/frontend/home/banners/content2',
            'button_text' => 'button_text2',
            'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url2',
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['home']['banners'][] = [
            'title' => 'data/cms/frontend/home/banners/title2',
            'content' => 'data/cms/frontend/home/banners/content2',
            'button_text' => 'button_text2',
            'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url2',
        ];

        $response->assertJson($this->settingsResponse);
    }

    /**
     * @test
     */
    public function multiple_home_banners_can_be_added()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        // Clear any existing banners
        $this->settingsData['cms']['frontend']['home']['banners'] = [];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['home']['banners'] = [];

        $response->assertJson($this->settingsResponse);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banner1/title',
                'content' => 'data/cms/frontend/home/banner1/content',
                'button_text' => 'button_text1',
                'button_url' => 'https://example.com/data/cms/frontend/home/banner1/button_url',
            ],
            [
                'title' => 'data/cms/frontend/home/banner2/title',
                'content' => 'data/cms/frontend/home/banner2/content',
                'button_text' => 'button_text2',
                'button_url' => 'https://example.com/data/cms/frontend/home/banner2/button_url',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banner1/title',
                'content' => 'data/cms/frontend/home/banner1/content',
                'button_text' => 'button_text1',
                'button_url' => 'https://example.com/data/cms/frontend/home/banner1/button_url',
            ],
            [
                'title' => 'data/cms/frontend/home/banner2/title',
                'content' => 'data/cms/frontend/home/banner2/content',
                'button_text' => 'button_text2',
                'button_url' => 'https://example.com/data/cms/frontend/home/banner2/button_url',
            ],
        ];

        $response->assertJson($this->settingsResponse);
    }

    /**
     * @test
     */
    public function home_banners_can_be_updated()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertJson($this->settingsResponse);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banners/title_updated',
                'content' => 'data/cms/frontend/home/banners/content_updated',
                'button_text' => 'button_text_updated',
                'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url_updated',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banners/title_updated',
                'content' => 'data/cms/frontend/home/banners/content_updated',
                'button_text' => 'button_text_updated',
                'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url_updated',
            ],
        ];
    }

    /**
     * @test
     */
    public function home_banners_require_title_content_button_text_and_button_url()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            null,
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [null],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => null,
                'content' => null,
                'button_text' => null,
                'button_url' => null,
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'content' => 'data/cms/frontend/home/banners/content_updated',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banners/title_updated',
                'content' => 'data/cms/frontend/home/banners/content_updated',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banners/title_updated',
                'content' => 'data/cms/frontend/home/banners/content_updated',
                'button_text' => 'button_text_updated',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banners/title_updated',
                'content' => 'data/cms/frontend/home/banners/content_updated',
                'button_text' => 'button_text_updated',
                'button_url' => 'www.not-a-valid-url',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->settingsData['cms']['frontend']['home']['banners'] = [
            [
                'title' => 'data/cms/frontend/home/banners/title_updated',
                'content' => 'data/cms/frontend/home/banners/content_updated',
                'button_text' => 'button_text_updated',
                'button_url' => 'https://example.com/data/cms/frontend/home/banners/button_url_updated',
            ],
        ];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $response->assertStatus(Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function home_banners_can_be_removed()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );

        // Clear any existing banners
        $this->settingsData['cms']['frontend']['home']['banners'] = [];

        $response = $this->putJson('/core/v1/settings', $this->settingsData);

        $this->settingsResponse['data']['cms']['frontend']['home']['banners'] = [];

        $response->assertJson($this->settingsResponse);
    }
}
