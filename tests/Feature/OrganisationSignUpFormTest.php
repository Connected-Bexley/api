<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\SocialMedia;
use App\Models\UpdateRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrganisationSignUpFormTest extends TestCase
{
    // Store.

    public function test_guest_can_create_one(): void
    {
        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => $this->faker->safeEmail(),
                'phone' => random_uk_mobile_phone(),
                'password' => 'P@55w0rd.',
            ],
            'organisation' => [
                'slug' => 'test-org',
                'name' => 'Test Org',
                'description' => 'Test description',
                'url' => 'http://test-org.example.com',
                'email' => 'info@test-org.example.com',
                'phone' => '07700000000',
            ],
            'service' => [
                'slug' => 'test-service',
                'name' => 'Test Service',
                'type' => Service::TYPE_SERVICE,
                'intro' => 'This is a test intro',
                'description' => 'Lorem ipsum',
                'wait_time' => null,
                'is_free' => true,
                'fees_text' => null,
                'fees_url' => null,
                'testimonial' => null,
                'video_embed' => null,
                'url' => $this->faker->url(),
                'contact_name' => $this->faker->name(),
                'contact_phone' => random_uk_mobile_phone(),
                'contact_email' => $this->faker->safeEmail(),
                'useful_infos' => [
                    [
                        'title' => 'Did you know?',
                        'description' => 'Lorem ipsum',
                        'order' => 1,
                    ],
                ],
                'offerings' => [
                    [
                        'offering' => 'Weekly club',
                        'order' => 1,
                    ],
                ],
                'social_medias' => [
                    [
                        'type' => SocialMedia::TYPE_INSTAGRAM,
                        'url' => 'https://www.instagram.com/ayupdigital',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_guest_can_create_one_with_single_form_of_contact(): void
    {
        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => $this->faker->safeEmail(),
                'phone' => random_uk_mobile_phone(),
                'password' => 'P@55w0rd.',
            ],
            'organisation' => [
                'slug' => 'test-org',
                'name' => 'Test Org',
                'description' => 'Test description',
                'url' => 'http://test-org.example.com',
                'email' => 'info@test-org.example.com',
                'phone' => null,
            ],
            'service' => [
                'slug' => 'test-service',
                'name' => 'Test Service',
                'type' => Service::TYPE_SERVICE,
                'intro' => 'This is a test intro',
                'description' => 'Lorem ipsum',
                'wait_time' => null,
                'is_free' => true,
                'fees_text' => null,
                'fees_url' => null,
                'testimonial' => null,
                'video_embed' => null,
                'url' => $this->faker->url(),
                'contact_name' => $this->faker->name(),
                'contact_phone' => random_uk_mobile_phone(),
                'contact_email' => null,
                'useful_infos' => [
                    [
                        'title' => 'Did you know?',
                        'description' => 'Lorem ipsum',
                        'order' => 1,
                    ],
                ],
                'offerings' => [
                    [
                        'offering' => 'Weekly club',
                        'order' => 1,
                    ],
                ],
                'social_medias' => [
                    [
                        'type' => SocialMedia::TYPE_INSTAGRAM,
                        'url' => 'https://www.instagram.com/ayupdigital',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    /**
     * @test
     */
    public function guest_can_sign_up_to_existing_organisation(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();

        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => [
                'id' => $organisation->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertEquals($userSubmission['email'], $this->getResponseContent($response, 'data.user.email'));
        $this->assertEquals($organisation->id, $this->getResponseContent($response, 'data.organisation.id'));
        $this->assertEquals($organisation->slug, $this->getResponseContent($response, 'data.organisation.slug'));
        $this->assertEquals($organisation->name, $this->getResponseContent($response, 'data.organisation.name'));
        $this->assertEquals($organisation->description, $this->getResponseContent($response, 'data.organisation.description'));
        $this->assertEquals($organisation->url, $this->getResponseContent($response, 'data.organisation.url'));
        $this->assertEquals($organisation->email, $this->getResponseContent($response, 'data.organisation.email'));
        $this->assertEquals($organisation->phone, $this->getResponseContent($response, 'data.organisation.phone'));

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            /** @var \App\Models\UpdateRequest $updateRequest */
            $updateRequest = UpdateRequest::findOrFail(
                $this->getResponseContent($response, 'id')
            );

            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser() === null) &&
                ($event->getModel()->is($updateRequest));
        });
    }

    /**
     * @test
     */
    public function guest_cannot_sign_up_with_existing_email(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();

        User::factory()->create([
            'email' => 'admin@organisation.org',
        ])->makeOrganisationAdmin($organisation);

        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => 'admin@organisation.org',
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => [
                'id' => $organisation->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function guest_must_sign_up_with_uk_mobile_phone_number(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();

        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => [
                'id' => $organisation->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => [
                'id' => $organisation->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    /**
     * @test
     */
    public function guest_cannot_sign_up_with_email_in_existing_signup_request(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();

        $user1Submission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => 'admin@organisation.org',
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $user1Submission,
            'organisation' => [
                'id' => $organisation->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $user2Submission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => 'admin@organisation.org',
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $user2Submission,
            'organisation' => [
                'id' => $organisation->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function guest_cannot_sign_up_to_non_existing_organisation(): void
    {
        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];
        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => [
                'id' => 'thisisnotanorganisationid',
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function guest_can_sign_up_with_new_organisation_without_service(): void
    {
        $this->fakeEvents();

        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];
        $organisationSubmission = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => null,
        ];
        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => $organisationSubmission,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertEquals($userSubmission['email'], $this->getResponseContent($response, 'data.user.email'));
        $this->assertEquals($organisationSubmission, $this->getResponseContent($response, 'data.organisation'));

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            /** @var \App\Models\UpdateRequest $updateRequest */
            $updateRequest = UpdateRequest::findOrFail(
                $this->getResponseContent($response, 'id')
            );

            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser() === null) &&
                ($event->getModel()->is($updateRequest));
        });
    }

    /**
     * @test
     */
    public function guest_cannot_sign_up_with_new_organisation_which_matches_existing_organisation(): void
    {
        $organisation = Organisation::factory()->create();

        $userSubmission = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => random_uk_mobile_phone(),
            'password' => 'P@55w0rd.',
        ];

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => $userSubmission,
            'organisation' => [
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => 'Test description',
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => null,
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_worker_cannot_create_one(): void
    {
        /** @var \App\Models\Service $service */
        $service = Service::factory()->create();
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one(): void
    {
        /** @var \App\Models\Service $service */
        $service = Service::factory()->create();
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one(): void
    {
        /** @var \App\Models\Organisation $organisation */
        $organisation = Organisation::factory()->create();
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_cannot_create_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_audit_created_when_created(): void
    {
        $this->fakeEvents();

        $response = $this->json('POST', '/core/v1/organisation-sign-up-forms', [
            'user' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => $this->faker->safeEmail(),
                'phone' => random_uk_mobile_phone(),
                'password' => 'P@55w0rd.',
            ],
            'organisation' => [
                'slug' => 'test-org',
                'name' => 'Test Org',
                'description' => 'Test description',
                'url' => 'http://test-org.example.com',
                'email' => 'info@test-org.example.com',
                'phone' => '07700000000',
            ],
            'service' => [
                'slug' => 'test-service',
                'name' => 'Test Service',
                'type' => Service::TYPE_SERVICE,
                'intro' => 'This is a test intro',
                'description' => 'Lorem ipsum',
                'wait_time' => null,
                'is_free' => true,
                'fees_text' => null,
                'fees_url' => null,
                'testimonial' => null,
                'video_embed' => null,
                'url' => $this->faker->url(),
                'contact_name' => $this->faker->name(),
                'contact_phone' => random_uk_mobile_phone(),
                'contact_email' => $this->faker->safeEmail(),
                'useful_infos' => [
                    [
                        'title' => 'Did you know?',
                        'description' => 'Lorem ipsum',
                        'order' => 1,
                    ],
                ],
                'offerings' => [
                    [
                        'offering' => 'Weekly club',
                        'order' => 1,
                    ],
                ],
                'social_medias' => [
                    [
                        'type' => SocialMedia::TYPE_INSTAGRAM,
                        'url' => 'https://www.instagram.com/ayupdigital',
                    ],
                ],
            ],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            /** @var \App\Models\UpdateRequest $updateRequest */
            $updateRequest = UpdateRequest::findOrFail(
                $this->getResponseContent($response, 'id')
            );

            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser() === null) &&
                ($event->getModel()->is($updateRequest));
        });
    }
}
