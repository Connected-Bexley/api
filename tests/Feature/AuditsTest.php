<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuditsTest extends TestCase
{
    /*
     * List all the audits.
     */

    /**
     * @test
     */
    public function guest_cannot_list_them(): void
    {
        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_list_them(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_list_them(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_list_them(): void
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_list_them(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $audit = Audit::create([
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $audit->id,
                'user_id' => null,
                'oauth_client' => null,
                'action' => Audit::ACTION_READ,
                'description' => 'Someone viewed a resource',
                'ip_address' => '127.0.0.1',
                'user_agent' => null,
                'created_at' => $this->now->format(CarbonImmutable::ISO8601),
                'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them_for_a_specific_user(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create()->makeSuperAdmin();
        $audit = Audit::create([
            'user_id' => $user->id,
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $anotherAudit = Audit::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits?filter[user_id]={$user->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $audit->id,
                'user_id' => $user->id,
                'oauth_client' => null,
                'action' => Audit::ACTION_READ,
                'description' => 'Someone viewed a resource',
                'ip_address' => '127.0.0.1',
                'user_agent' => null,
                'created_at' => $this->now->format(CarbonImmutable::ISO8601),
                'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
            ],
        ]);
        $response->assertJsonMissing([
            [
                'id' => $anotherAudit->id,
                'user_id' => $anotherAudit->user_id,
                'oauth_client' => null,
                'action' => $anotherAudit->action,
                'description' => $anotherAudit->description,
                'ip_address' => $anotherAudit->ip_address,
                'user_agent' => $anotherAudit->user_agent,
                'created_at' => $anotherAudit->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $anotherAudit->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed(): void
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $this->json('GET', '/core/v1/audits');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /*
     * Get a specific audit.
     */

    /**
     * @test
     */
    public function guest_cannot_view_one(): void
    {
        $audit = Audit::factory()->create();

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_view_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $audit = Audit::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_view_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $audit = Audit::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_view_one(): void
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $audit = Audit::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_view_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $audit = Audit::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_view_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $audit = Audit::create([
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $audit->id,
                'user_id' => null,
                'oauth_client' => null,
                'action' => Audit::ACTION_READ,
                'description' => 'Someone viewed a resource',
                'ip_address' => '127.0.0.1',
                'user_agent' => null,
                'created_at' => $this->now->format(CarbonImmutable::ISO8601),
                'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed(): void
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $audit = Audit::create([
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Event::fake();
        $this->json('GET', "/core/v1/audits/{$audit->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $audit) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $audit->id);
        });
    }
}
