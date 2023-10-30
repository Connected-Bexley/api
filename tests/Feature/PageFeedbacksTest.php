<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\PageFeedback;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PageFeedbacksTest extends TestCase
{
    /*
     * List all the page feedbacks.
     */

    /**
     * @test
     */
    public function guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/page-feedbacks');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_list_them()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/page-feedbacks');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/page-feedbacks');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $service = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/page-feedbacks');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/page-feedbacks');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'consented_at' => $this->now,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/page-feedbacks');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $pageFeedback->id,
                'url' => url('/test'),
                'feedback' => 'This page does not work',
                'name' => null,
                'email' => null,
                'phone' => null,
                'consented_at' => $pageFeedback->consented_at->format(CarbonImmutable::ISO8601),
                'created_at' => $pageFeedback->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $pageFeedback->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();

        Passport::actingAs($user);

        $this->json('GET', '/core/v1/page-feedbacks');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /*
     * Create a page feedback.
     */

    /**
     * @test
     */
    public function guest_can_create_one()
    {
        $payload = [
            'url' => url('test-page'),
            'feedback' => 'This page does not work',
            'name' => null,
            'email' => null,
            'phone' => null,
        ];

        $response = $this->json('POST', '/core/v1/page-feedbacks', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function audit_created_when_created()
    {
        $this->fakeEvents();

        $response = $this->json('POST', '/core/v1/page-feedbacks', [
            'url' => url('test-page'),
            'feedback' => 'This page does not work',
            'name' => null,
            'email' => null,
            'phone' => null,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific page feedback.
     */

    /**
     * @test
     */
    public function guest_cannot_view_one()
    {
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $response = $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_view_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $service = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($service);
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create()->makeGlobalAdmin();
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_view_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'consented_at' => $this->now,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id' => $pageFeedback->id,
                'url' => url('/test'),
                'feedback' => 'This page does not work',
                'name' => null,
                'email' => null,
                'phone' => null,
                'consented_at' => $pageFeedback->consented_at->format(CarbonImmutable::ISO8601),
                'created_at' => $pageFeedback->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $pageFeedback->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $pageFeedback = PageFeedback::create([
            'url' => url('/test'),
            'feedback' => 'This page does not work',
            'consented_at' => $this->now,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/page-feedbacks/{$pageFeedback->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $pageFeedback) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $pageFeedback->id);
        });
    }
}
