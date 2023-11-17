<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Referral;
use App\Models\Service;
use App\Models\StatusUpdate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReferralsTest extends TestCase
{
    /*
     * List all the referrals.
     */

    /**
     * @test
     */
    public function guest_cannot_list_them(): void
    {
        $response = $this->json('GET', '/core/v1/referrals');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function global_admin_cannot_list_them(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeGlobalAdmin();
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals?filter[service_id]={$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_worker_for_another_service_cannot_list_them(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $anotherService = Service::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals?filter[service_id]={$anotherService->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => []]);
    }

    /**
     * @test
     */
    public function service_worker_can_list_them(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals?filter[service_id]={$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $referral->id,
                'service_id' => $referral->service_id,
                'reference' => $referral->reference,
                'status' => $referral->status,
                'name' => $referral->name,
                'email' => $referral->email,
                'phone' => $referral->phone,
                'other_contact' => $referral->other_contact,
                'postcode_outward_code' => $referral->postcode_outward_code,
                'comments' => $referral->comments,
                'referral_consented_at' => $referral->referral_consented_at->format(CarbonImmutable::ISO8601),
                'feedback_consented_at' => null,
                'referee_name' => $referral->referee_name,
                'referee_email' => $referral->referee_email,
                'referee_phone' => $referral->referee_phone,
                'referee_organisation' => $referral->organisation,
                'created_at' => $referral->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $referral->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function only_referrals_user_is_authorised_to_view_are_shown(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);
        $anotherReferral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/referrals');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $referral->id]);
        $response->assertJsonMissing(['id' => $anotherReferral->id]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed(): void
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/referrals?filter[service_id]={$service->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /**
     * @test
     */
    public function super_admin_can_filter_by_organisation_name(): void
    {
        /**
         * @var \App\Models\Organisation $organisationOne
         * @var \App\Models\Service $serviceOne
         */
        $organisationOne = Organisation::factory()->create([
            'name' => 'Organisation One',
        ]);
        $serviceOne = Service::factory()->create([
            'organisation_id' => $organisationOne->id,
        ]);
        $referralOne = Referral::factory()->create([
            'service_id' => $serviceOne->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /**
         * @var \App\Models\Organisation $organisationTwo
         * @var \App\Models\Service $serviceTwo
         */
        $organisationTwo = Organisation::factory()->create([
            'name' => 'Organisation Two',
        ]);
        $serviceTwo = Service::factory()->create([
            'organisation_id' => $organisationTwo->id,
        ]);
        $referralTwo = Referral::factory()->create([
            'service_id' => $serviceTwo->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        Passport::actingAs(User::factory()->create()->makeSuperAdmin());

        $response = $this->json('GET', "/core/v1/referrals?filter[organisation_name]={$organisationOne->name}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $referralOne->id]);
        $response->assertJsonMissing(['id' => $referralTwo->id]);
    }

    /**
     * @test
     */
    public function super_admin_can_filter_by_service_name(): void
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = Service::factory()->create([
            'name' => 'Service One',
        ]);
        $referralOne = Referral::factory()->create([
            'service_id' => $serviceOne->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = Service::factory()->create([
            'name' => 'Service Two',
        ]);
        $referralTwo = Referral::factory()->create([
            'service_id' => $serviceTwo->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals?filter[service_name]={$serviceOne->name}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $referralOne->id]);
        $response->assertJsonMissing(['id' => $referralTwo->id]);
    }

    /**
     * @test
     */
    public function super_admin_can_sort_by_organisation_name(): void
    {
        /**
         * @var \App\Models\Organisation $organisationOne
         * @var \App\Models\Service $serviceOne
         */
        $organisationOne = Organisation::factory()->create([
            'name' => 'Organisation A',
        ]);
        $serviceOne = Service::factory()->create([
            'organisation_id' => $organisationOne->id,
        ]);
        $referralOne = Referral::factory()->create([
            'service_id' => $serviceOne->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /**
         * @var \App\Models\Organisation $organisationTwo
         * @var \App\Models\Service $serviceTwo
         */
        $organisationTwo = Organisation::factory()->create([
            'name' => 'Organisation B',
        ]);
        $serviceTwo = Service::factory()->create([
            'organisation_id' => $organisationTwo->id,
        ]);
        $referralTwo = Referral::factory()->create([
            'service_id' => $serviceTwo->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/referrals?sort=-organisation_name');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($referralOne->id, $data['data'][1]['id']);
        $this->assertEquals($referralTwo->id, $data['data'][0]['id']);
    }

    /**
     * @test
     */
    public function super_admin_can_sort_by_service_name(): void
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = Service::factory()->create([
            'name' => 'Service A',
        ]);
        $referralOne = Referral::factory()->create([
            'service_id' => $serviceOne->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /** @var \App\Models\Organisation $organisationTwo */
        $serviceTwo = Service::factory()->create([
            'name' => 'Service B',
        ]);
        $referralTwo = Referral::factory()->create([
            'service_id' => $serviceTwo->id,
            'email' => $this->faker->safeEmail(),
            'comments' => $this->faker->paragraph(),
            'referral_consented_at' => $this->now,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/referrals?sort=-service_name');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($referralOne->id, $data['data'][1]['id']);
        $this->assertEquals($referralTwo->id, $data['data'][0]['id']);
    }

    /*
     * Create a referral.
     */

    /**
     * @test
     */
    public function guest_can_create_referral(): void
    {
        $service = Service::factory()->create([
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail(),
        ]);

        $payload = [
            'service_id' => $service->id,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => null,
            'other_contact' => null,
            'postcode_outward_code' => null,
            'comments' => null,
            'referral_consented' => true,
            'feedback_consented' => false,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ];

        $response = $this->json('POST', '/core/v1/referrals', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'service_id' => $payload['service_id'],
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'other_contact' => $payload['other_contact'],
            'postcode_outward_code' => $payload['postcode_outward_code'],
            'comments' => $payload['comments'],
            'referee_name' => $payload['referee_name'],
            'referee_email' => $payload['referee_email'],
            'referee_phone' => $payload['referee_phone'],
            'referee_organisation' => $payload['organisation'],
        ]);
    }

    /**
     * @test
     */
    public function guest_can_create_self_referral(): void
    {
        $service = Service::factory()->create([
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail(),
        ]);

        $payload = [
            'service_id' => $service->id,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => null,
            'other_contact' => null,
            'postcode_outward_code' => null,
            'comments' => null,
            'referral_consented' => true,
            'feedback_consented' => false,
        ];

        $response = $this->json('POST', '/core/v1/referrals', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'service_id' => $service->id,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => null,
            'other_contact' => null,
            'postcode_outward_code' => null,
            'comments' => null,
            'referee_name' => null,
            'referee_email' => null,
            'referee_phone' => null,
            'referee_organisation' => null,
        ]);
    }

    /**
     * @test
     */
    public function guest_can_create_referral_for_a_service_without_a_contact_method(): void
    {
        $service = Service::factory()->create();

        $payload = [
            'service_id' => $service->id,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => null,
            'other_contact' => null,
            'postcode_outward_code' => null,
            'comments' => null,
            'referral_consented' => true,
            'feedback_consented' => false,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ];

        $response = $this->json('POST', '/core/v1/referrals', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'service_id' => $payload['service_id'],
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'other_contact' => $payload['other_contact'],
            'postcode_outward_code' => $payload['postcode_outward_code'],
            'comments' => $payload['comments'],
            'referee_name' => $payload['referee_name'],
            'referee_email' => $payload['referee_email'],
            'referee_phone' => $payload['referee_phone'],
            'referee_organisation' => $payload['organisation'],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_created(): void
    {
        $this->fakeEvents();

        $service = Service::factory()->create([
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail(),
        ]);

        $response = $this->json('POST', '/core/v1/referrals', [
            'service_id' => $service->id,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => null,
            'other_contact' => null,
            'postcode_outward_code' => null,
            'comments' => null,
            'referral_consented' => true,
            'feedback_consented' => false,
            'referee_name' => $this->faker->name(),
            'referee_email' => $this->faker->safeEmail(),
            'referee_phone' => random_uk_phone(),
            'organisation' => $this->faker->company(),
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific referral.
     */

    /**
     * @test
     */
    public function guest_cannot_view_one(): void
    {
        $referral = Referral::factory()->create();

        $response = $this->json('GET', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_for_another_service_cannot_view_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_view_one(): void
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_worker_can_view_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'referral_consented_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id' => $referral->id,
                'service_id' => $referral->service_id,
                'reference' => $referral->reference,
                'status' => Referral::STATUS_NEW,
                'name' => $referral->name,
                'email' => null,
                'phone' => null,
                'other_contact' => null,
                'postcode_outward_code' => null,
                'comments' => null,
                'referral_consented_at' => $this->now->format(CarbonImmutable::ISO8601),
                'feedback_consented_at' => null,
                'referee_name' => null,
                'referee_email' => null,
                'referee_phone' => null,
                'referee_organisation' => null,
                'created_at' => $referral->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $referral->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed(): void
    {
        $this->fakeEvents();

        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'referral_consented_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/referrals/{$referral->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $referral) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $referral->id);
        });
    }

    /*
     * Update a specific referral.
     */

    /**
     * @test
     */
    public function guest_cannot_update_one(): void
    {
        $referral = Referral::factory()->create();

        $response = $this->json('PUT', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_for_another_service_cannot_update_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/referrals/{$referral->id}", [
            'status' => Referral::STATUS_IN_PROGRESS,
            'comments' => 'Assigned to me',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_update_one(): void
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/referrals/{$referral->id}", [
            'status' => Referral::STATUS_IN_PROGRESS,
            'comments' => 'Assigned to me',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_worker_can_update_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'referral_consented_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/referrals/{$referral->id}", [
            'status' => Referral::STATUS_IN_PROGRESS,
            'comments' => 'Assigned to me',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id' => $referral->id,
                'service_id' => $referral->service_id,
                'reference' => $referral->reference,
                'status' => Referral::STATUS_IN_PROGRESS,
                'name' => $referral->name,
                'email' => null,
                'phone' => null,
                'other_contact' => null,
                'postcode_outward_code' => null,
                'comments' => null,
                'referral_consented_at' => $this->now->format(CarbonImmutable::ISO8601),
                'feedback_consented_at' => null,
                'referee_name' => null,
                'referee_email' => null,
                'referee_phone' => null,
                'referee_organisation' => null,
                'created_at' => $referral->created_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
        $this->assertDatabaseHas((new StatusUpdate())->getTable(), [
            'user_id' => $user->id,
            'referral_id' => $referral->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_IN_PROGRESS,
            'comments' => 'Assigned to me',
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_updated(): void
    {
        $this->fakeEvents();

        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create([
            'service_id' => $service->id,
            'referral_consented_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/referrals/{$referral->id}", [
            'status' => Referral::STATUS_IN_PROGRESS,
            'comments' => 'Assigned to me',
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $referral) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $referral->id);
        });
    }

    /*
     * Delete a specific referral.
     */

    /**
     * @test
     */
    public function guest_cannot_delete_one(): void
    {
        $referral = Referral::factory()->create();

        $response = $this->json('DELETE', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_delete_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_delete_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_delete_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_delete_one(): void
    {
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_cannot_delete_one(): void
    {
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $referral = Referral::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/referrals/{$referral->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing(table(Referral::class), ['id' => $referral->id]);
    }
}
